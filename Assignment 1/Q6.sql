-- Q6
SELECT
    AnimalID, -- animal's ID
    COALESCE(Name, 'Unknown') AS AnimalName -- use Name if present, else 'Unknown'
FROM Animal; -- every animal, named or not