-- Q1
SELECT
    c.CustomerID,   -- customers ID
    CONCAT(c.LastName, ', ', c.FirstName) AS CustomerName, -- format into LAST, FIRST
    a.AnimalID, -- animals ID
    a.Name AS AnimalName -- animals Name
FROM Customer c -- start from customer 
JOIN Sale s ON c.CustomerID = s.CustomerID -- customer -> sale
JOIN SaleAnimal sa ON s.SaleID = sa.SaleID -- sale --> animal sale 
JOIN Animal a ON sa.AnimalID = a.AnimalID -- link to actual animal 
ORDER BY c.FirstName, c.LastName; -- sort by first, last 
