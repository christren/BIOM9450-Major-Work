SELECT Name, Breed, ListPrice
FROM Animal
WHERE Category = 'Dog'
    And ListPrice > 250
ORDER BY ListPrice DESC;