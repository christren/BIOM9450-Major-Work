-- Q2
SELECT
    e.EmployeeID,   -- employee's ID
    CONCAT(e.LastName, ', ', e.FirstName) AS EmployeeName,  -- merge into "LAST, First"
    SUM(si.Quantity) AS TotalItemsSold  -- sum quantity across all their sales
FROM Employee e  -- start from Employee
JOIN Sale s ON e.EmployeeID = s.EmployeeID  -- link employee to their sales
JOIN SaleItem si ON s.SaleID = si.SaleID  -- link sale to merchandise line items
GROUP BY e.EmployeeID, e.LastName, e.FirstName  -- collapse rows to one per employee
ORDER BY e.LastName;  -- sort by employee last name