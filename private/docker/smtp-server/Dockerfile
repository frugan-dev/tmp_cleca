FROM python:3.11-slim

# Install aiosmtpd
RUN pip install aiosmtpd

# Copy the server script
COPY smtp_server.py /smtp_server.py
RUN chmod +x /smtp_server.py

# Expose SMTP port
EXPOSE 587

# Run the server
CMD ["python", "/smtp_server.py"]
