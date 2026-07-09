/*
|--------------------------------------------------------------------------
| RELATÓRIOS DE VEÍCULOS
|--------------------------------------------------------------------------
*/

------------------------------------------------------------
-- Todos os veículos
------------------------------------------------------------

SELECT
    v.id,
    b.name AS brand,
    v.model,
    v.year,
    v.color,
    v.license_plate,
    p.name AS owner
FROM vehicle v
INNER JOIN people p
    ON p.id = v.people_id
INNER JOIN brands b
    ON b.id = v.brand_id
ORDER BY p.name;

------------------------------------------------------------
-- Veículos por pessoa
------------------------------------------------------------

SELECT
    p.name,
    COUNT(v.id) AS total_vehicles
FROM people p
LEFT JOIN vehicle v
    ON v.people_id = p.id
GROUP BY p.id, p.name
ORDER BY p.name;

------------------------------------------------------------
-- Homens x Mulheres com mais veículos
------------------------------------------------------------

SELECT
    p.gender,
    COUNT(v.id) AS total
FROM people p
INNER JOIN vehicle v
    ON v.people_id = p.id
GROUP BY p.gender;

------------------------------------------------------------
-- Marcas ordenadas pela quantidade
------------------------------------------------------------

SELECT
    b.name,
    COUNT(v.id) AS total
FROM vehicle v
INNER JOIN brands b
    ON b.id = v.brand_id
GROUP BY b.name
ORDER BY total DESC;

------------------------------------------------------------
-- Marcas por gênero
------------------------------------------------------------

SELECT
    p.gender,
    b.name,
    COUNT(v.id) AS total
FROM vehicle v
INNER JOIN people p
    ON p.id = v.people_id
INNER JOIN brands b
    ON b.id = v.brand_id
GROUP BY
    p.gender,
    b.name
ORDER BY
    p.gender,
    total DESC;