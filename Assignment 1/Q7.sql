-- Q7
SELECT DISTINCT
    CONCAT(c.LastName, ', ', c.FirstName) AS CustomerName  -- one name per qualifying customer
FROM Customer c
JOIN Sale s ON c.CustomerID = s.CustomerID  -- customer's sales
JOIN SaleItem si ON s.SaleID = si.SaleID  -- merchandise sold in that sale
JOIN Merchandise m ON si.ItemID = m.ItemID  -- merchandise category
JOIN SaleAnimal sa ON s.SaleID = sa.SaleID  -- animal sold in the SAME sale
JOIN Animal a ON sa.AnimalID = a.AnimalID  -- animal category
WHERE m.Category = a.Category;  -- exclude mismatched categories