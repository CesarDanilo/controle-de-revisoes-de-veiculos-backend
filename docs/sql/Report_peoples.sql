/*
|--------------------------------------------------------------------------
| RELATÓRIOS DE PESSOAS
|--------------------------------------------------------------------------
*/

------------------------------------------------------------
-- Todas as pessoas
------------------------------------------------------------

SELECT
    id,
    name,
    email,
    phone,
    document,
    birth_date,
    gender
FROM people
ORDER BY name;

------------------------------------------------------------
-- Total de pessoas
------------------------------------------------------------

SELECT COUNT(*) AS total_people
FROM people;

------------------------------------------------------------
-- Idade média dos homens
------------------------------------------------------------

SELECT
    ROUND(AVG(EXTRACT(YEAR FROM AGE(birth_date))),0) AS average_age
FROM people
WHERE gender = 'M';

------------------------------------------------------------
-- Idade média das mulheres
------------------------------------------------------------

SELECT
    ROUND(AVG(EXTRACT(YEAR FROM AGE(birth_date))),0) AS average_age
FROM people
WHERE gender = 'F';

------------------------------------------------------------
-- Distribuição por gênero
------------------------------------------------------------

SELECT
    gender,
    COUNT(*) AS total
FROM people
GROUP BY gender;