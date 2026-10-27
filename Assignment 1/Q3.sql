-- Q3
SELECT
    COUNT(DISTINCT a.AnimalID) AS TotalCats,  -- all cats, sold or not
    COUNT(DISTINCT sa.AnimalID) AS CatsSold  -- only cats with a matching sale
FROM Animal a  -- start from every animal
LEFT JOIN SaleAnimal sa ON a.AnimalID = sa.AnimalID  -- keep unmatched (unsold) cats too
WHERE a.Category = 'Cat';  -- restrict to cats only