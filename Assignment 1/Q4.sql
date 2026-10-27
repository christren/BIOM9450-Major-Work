-- Q4
SELECT SaleID, SUM(Amount) AS TotalAmount  -- combine merch + animal totals per sale
FROM (
    SELECT SaleID, SUM(Quantity * SalePrice) AS Amount  -- merch subtotal per sale
    FROM SaleItem
    GROUP BY SaleID
    UNION ALL  -- stack rows, don't dedupe yet
    SELECT SaleID, SUM(SalePrice) AS Amount  -- animal subtotal per sale
    FROM SaleAnimal
    GROUP BY SaleID
) AS Combined
GROUP BY SaleID  -- merge merch + animal rows per sale
ORDER BY TotalAmount DESC;  -- highest spending sales first