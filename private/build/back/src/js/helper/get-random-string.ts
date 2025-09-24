// @ts-nocheck
/**
 * Generates a random string of specified length
 *
 * Two different approaches:
 *
 * 1. Character-based approach (RECOMMENDED):
 *    - Always guarantees exact length
 *    - Full character set: A-Z, a-z, 0-9 (62 characters)
 *    - Uniform distribution across all characters
 *    - Predictable and reliable
 *    - Better collision resistance due to larger character set
 *
 * 2. Math.random().toString(36) approach (NOT RECOMMENDED):
 *    - Does NOT guarantee exact length (can return shorter strings)
 *    - Limited character set: 0-9, a-z only (36 characters, no uppercase)
 *    - Unpredictable length due to Math.random() behavior
 *    - When Math.random() generates small numbers (e.g., 0.01),
 *      toString(36) produces fewer characters than expected
 *
 * For DOM element IDs and similar use cases where consistent length
 * and character variety are important, use the first approach.
 */
export const getRandomString = (length = 8) => {
  const chars =
    'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let result = '';
  for (let index = 0; index < length; index++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
};

// Alternative approach - NOT RECOMMENDED due to unpredictable length
// export const getRandomString = (length = 8) => {
//     return Math.random()
//         .toString(36)
//         .slice(2, 2 + length);
// };
