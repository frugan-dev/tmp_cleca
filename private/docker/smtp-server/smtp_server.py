#!/usr/bin/env python3
"""
Minimal OAuth2 SMTP test server - bypasses aiosmtpd completely
"""

import asyncio
import base64
import logging

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


class MinimalSMTPServer:
    def __init__(self):
        self.authenticated_sessions = set()

    async def handle_client(self, reader, writer):
        """Handle SMTP client connection"""
        addr = writer.get_extra_info('peername')
        logger.info(f"Connection from {addr}")
        
        # Send greeting
        writer.write(b'220 smtp-server ESMTP OAuth2 Test Server\r\n')
        await writer.drain()
        
        try:
            session_id = id(writer)
            
            while True:
                data = await reader.readline()
                if not data:
                    break
                    
                line = data.decode().strip()
                logger.info(f"({addr}) >> {line}")
                
                if line.upper().startswith('EHLO'):
                    await self.handle_ehlo(writer, line)
                elif line.upper().startswith('MAIL FROM:'):
                    await self.handle_mail(writer, line, session_id)
                elif line.upper().startswith('RCPT TO:'):
                    await self.handle_rcpt(writer, line)
                elif line.upper().startswith('DATA'):
                    await self.handle_data(writer, reader)
                elif line.upper().startswith('AUTH'):
                    await self.handle_auth(writer, reader, line, session_id)
                elif line.upper().startswith('RSET'):
                    await self.handle_rset(writer)
                elif line.upper().startswith('QUIT'):
                    await self.handle_quit(writer)
                    break
                else:
                    writer.write(b'500 Command not recognized\r\n')
                    await writer.drain()
                    
        except Exception as e:
            logger.error(f"Error handling client {addr}: {e}")
        finally:
            writer.close()
            await writer.wait_closed()
            logger.info(f"Connection closed: {addr}")

    async def handle_ehlo(self, writer, line):
        """Handle EHLO command"""
        hostname = line.split(' ', 1)[1] if ' ' in line else 'unknown'
        logger.info(f"EHLO from {hostname}")
        
        writer.write(b'250-smtp-server Hello\r\n')
        writer.write(b'250-SIZE 33554432\r\n')
        writer.write(b'250-8BITMIME\r\n')
        writer.write(b'250-AUTH XOAUTH2 PLAIN LOGIN\r\n')
        writer.write(b'250 HELP\r\n')
        await writer.drain()
        
        logger.info("EHLO response sent with AUTH XOAUTH2 PLAIN LOGIN")

    async def handle_mail(self, writer, line, session_id):
        """Handle MAIL FROM command - require authentication"""
        if session_id not in self.authenticated_sessions:
            logger.warning("MAIL FROM attempted without authentication")
            writer.write(b'530 5.7.0 Authentication required\r\n')
            await writer.drain()
            return
            
        logger.info(f"MAIL FROM accepted (authenticated)")
        writer.write(b'250 2.1.0 OK\r\n')
        await writer.drain()

    async def handle_rcpt(self, writer, line):
        """Handle RCPT TO command"""
        logger.info(f"RCPT TO accepted")
        writer.write(b'250 2.1.5 OK\r\n')
        await writer.drain()

    async def handle_data(self, writer, reader):
        """Handle DATA command"""
        logger.info("DATA command received")
        writer.write(b'354 End data with <CR><LF>.<CR><LF>\r\n')
        await writer.drain()
        
        # Read message data until ".\r\n"
        message_lines = []
        while True:
            line = await reader.readline()
            if line == b'.\r\n':
                break
            message_lines.append(line)
        
        message_size = sum(len(line) for line in message_lines)
        logger.info(f"Message received, size: {message_size} bytes")
        
        writer.write(b'250 2.0.0 OK: queued\r\n')
        await writer.drain()

    async def handle_auth(self, writer, reader, line, session_id):
        """Handle AUTH command"""
        parts = line.split(' ', 2)
        if len(parts) < 2:
            writer.write(b'501 Syntax error in parameters\r\n')
            await writer.drain()
            return
            
        mechanism = parts[1].upper()
        auth_data = parts[2] if len(parts) > 2 else ''
        
        logger.info(f"AUTH command: mechanism={mechanism}")
        
        if mechanism == 'XOAUTH2':
            await self.handle_xoauth2_auth(writer, reader, auth_data, session_id)
        elif mechanism == 'PLAIN':
            await self.handle_plain_auth(writer, reader, auth_data, session_id)
        elif mechanism == 'LOGIN':
            await self.handle_login_auth(writer, reader, auth_data, session_id)
        else:
            logger.warning(f"Unsupported AUTH mechanism: {mechanism}")
            writer.write(b'504 5.7.4 Authentication mechanism not supported\r\n')
            await writer.drain()

    async def handle_xoauth2_auth(self, writer, reader, auth_data, session_id):
        """Handle XOAUTH2 authentication"""
        logger.info("Processing XOAUTH2 authentication")
        
        try:
            if not auth_data:
                # Challenge for auth data
                writer.write(b'334 \r\n')
                await writer.drain()
                
                response = await reader.readline()
                auth_data = response.decode().strip()
                logger.info(f"Received XOAUTH2 data: {auth_data[:50]}...")
            
            # Decode base64 auth string
            decoded = base64.b64decode(auth_data).decode('utf-8')
            logger.info(f"Decoded XOAUTH2: {decoded[:100]}...")
            
            # Parse OAuth2 string: user=email\x01auth=Bearer token\x01\x01
            if 'user=' in decoded and 'auth=Bearer' in decoded:
                parts = decoded.split('\x01')
                user_part = next((p for p in parts if p.startswith('user=')), None)
                auth_part = next((p for p in parts if p.startswith('auth=Bearer')), None)
                
                if user_part and auth_part:
                    username = user_part.split('=', 1)[1]
                    token = auth_part.split('Bearer ', 1)[1]
                    
                    logger.info(f"OAuth2 user: {username}")
                    logger.info(f"Token (first 20 chars): {token[:20]}...")
                    
                    # Accept any token for testing
                    self.authenticated_sessions.add(session_id)
                    writer.write(b'235 2.7.0 Authentication successful\r\n')
                    await writer.drain()
                    logger.info("XOAUTH2 authentication successful!")
                    return
            
            logger.warning("Invalid XOAUTH2 format")
            writer.write(b'535 5.7.8 Authentication failed\r\n')
            await writer.drain()
            
        except Exception as e:
            logger.error(f"XOAUTH2 error: {e}")
            writer.write(b'535 5.7.8 Authentication failed\r\n')
            await writer.drain()

    async def handle_plain_auth(self, writer, reader, auth_data, session_id):
        """Handle PLAIN authentication"""
        logger.info("Processing PLAIN authentication")
        self.authenticated_sessions.add(session_id)
        writer.write(b'235 2.7.0 Authentication successful\r\n')
        await writer.drain()

    async def handle_login_auth(self, writer, reader, auth_data, session_id):
        """Handle LOGIN authentication"""
        logger.info("Processing LOGIN authentication")
        self.authenticated_sessions.add(session_id)
        writer.write(b'235 2.7.0 Authentication successful\r\n')
        await writer.drain()

    async def handle_rset(self, writer):
        """Handle RSET command"""
        writer.write(b'250 2.0.0 OK\r\n')
        await writer.drain()

    async def handle_quit(self, writer):
        """Handle QUIT command"""
        writer.write(b'221 2.0.0 Bye\r\n')
        await writer.drain()


async def main():
    """Start the minimal OAuth2 SMTP server"""
    host = '0.0.0.0'
    port = 587
    
    logger.info("Starting Minimal OAuth2 SMTP test server...")
    logger.info(f"Server will listen on {host}:{port}")
    logger.info("Supported AUTH mechanisms: XOAUTH2, PLAIN, LOGIN")
    
    smtp_server = MinimalSMTPServer()
    
    server = await asyncio.start_server(
        smtp_server.handle_client,
        host,
        port
    )
    
    logger.info(f"OAuth2 SMTP server running on {host}:{port}")
    logger.info("Press Ctrl+C to stop the server")
    
    try:
        await server.serve_forever()
    except KeyboardInterrupt:
        logger.info("Shutting down server...")
    finally:
        server.close()
        await server.wait_closed()


if __name__ == '__main__':
    asyncio.run(main())