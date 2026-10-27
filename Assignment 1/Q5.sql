-- Q5
SELECT
    COUNT(DISTINCT ao.OrderID) AS NumOrders,  -- count of qualifying orders
    SUM(aoi.Cost) AS TotalPrice  -- total item cost across those orders
FROM AnimalOrder ao  -- start from the order header
JOIN AnimalOrderItem aoi ON ao.OrderID = aoi.OrderId  -- link to items costed in each order
WHERE DATEDIFF(ao.ReceiveDate, ao.OrderDate) <= 14;  -- filter: 14 days or less turnaround