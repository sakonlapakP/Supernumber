-- Normalize postpaid package price from 1099 to 1199.
-- Usage: select the target production database first, then run this script.

SET @OLD_SQL_SAFE_UPDATES := @@SQL_SAFE_UPDATES;
SET SQL_SAFE_UPDATES = 0;

START TRANSACTION;

UPDATE phone_numbers
SET sale_price = 1199,
    price_text = '1199'
WHERE service_type = 'postpaid'
  AND (sale_price = 1099 OR TRIM(COALESCE(price_text, '')) = '1099');

SELECT 'normalized_1099_to_1199_rows' AS metric, ROW_COUNT() AS value;

COMMIT;

SET SQL_SAFE_UPDATES = @OLD_SQL_SAFE_UPDATES;

SELECT 'postpaid_price' AS metric, COALESCE(CAST(sale_price AS CHAR), 'NULL') AS label, COUNT(*) AS value
FROM phone_numbers
WHERE service_type = 'postpaid'
GROUP BY sale_price
ORDER BY sale_price;
