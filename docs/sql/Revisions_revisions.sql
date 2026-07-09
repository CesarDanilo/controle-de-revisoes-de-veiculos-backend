/*
|--------------------------------------------------------------------------
| RELATÓRIOS DE REVISÕES
|--------------------------------------------------------------------------
*/

------------------------------------------------------------
-- Revisões por período
------------------------------------------------------------

SELECT
    r.revision_date,
    p.name,
    b.name AS brand,
    v.model,
    r.description,
    r.cost
FROM revisions r
INNER JOIN vehicle v
    ON v.id = r.vehicle_id
INNER JOIN brands b
    ON b.id = v.brand_id
INNER JOIN people p
    ON p.id = v.people_id
WHERE r.revision_date BETWEEN :start_date AND :end_date
ORDER BY r.revision_date DESC;

------------------------------------------------------------
-- Marcas com mais revisões
------------------------------------------------------------

SELECT
    b.name,
    COUNT(r.id) AS total
FROM revisions r
INNER JOIN vehicle v
    ON v.id = r.vehicle_id
INNER JOIN brands b
    ON b.id = v.brand_id
GROUP BY b.name
ORDER BY total DESC;

------------------------------------------------------------
-- Pessoas com mais revisões
------------------------------------------------------------

SELECT
    p.name,
    COUNT(r.id) AS total
FROM revisions r
INNER JOIN vehicle v
    ON v.id = r.vehicle_id
INNER JOIN people p
    ON p.id = v.people_id
GROUP BY p.name
ORDER BY total DESC;

------------------------------------------------------------
-- Média entre revisões
------------------------------------------------------------

WITH revision_history AS (

SELECT

    p.name,

    r.revision_date,

    LAG(r.revision_date)
        OVER (
            PARTITION BY p.id
            ORDER BY r.revision_date
        ) previous_revision

FROM revisions r

INNER JOIN vehicle v
    ON v.id = r.vehicle_id

INNER JOIN people p
    ON p.id = v.people_id

)

SELECT

    name,

    ROUND(
        AVG(revision_date - previous_revision),
        0
    ) AS average_days

FROM revision_history

WHERE previous_revision IS NOT NULL

GROUP BY name;

------------------------------------------------------------
-- Próximas revisões previstas
------------------------------------------------------------

SELECT

    p.name,

    v.model,

    r.next_revision_date,

    r.next_revision_km

FROM revisions r

INNER JOIN vehicle v
    ON v.id = r.vehicle_id

INNER JOIN people p
    ON p.id = v.people_id

ORDER BY r.next_revision_date;