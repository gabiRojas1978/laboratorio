-- Guardar ambos porcentajes del resultado Tetrazolio y corregir los registros existentes.
ALTER TABLE boletines_tetrazolio
    ADD COLUMN resultado_no_viables_porcentaje DECIMAL(6,2) NULL AFTER resultado_porcentaje;

UPDATE boletines_tetrazolio AS b
LEFT JOIN (
    SELECT id_boletin_tetrazolio,
           SUM(semillas_viables) AS semillas_viables,
           SUM(semillas_no_viables) AS semillas_no_viables
    FROM boletin_tetrazolio_repeticiones
    GROUP BY id_boletin_tetrazolio
) AS r ON r.id_boletin_tetrazolio = b.id_boletin_tetrazolio
SET b.resultado_porcentaje = CASE
        WHEN COALESCE(r.semillas_viables, 0) + COALESCE(r.semillas_no_viables, 0) > 0
        THEN ROUND(COALESCE(r.semillas_viables, 0) * 100.0 / (r.semillas_viables + r.semillas_no_viables), 2)
        ELSE b.resultado_porcentaje
    END,
    b.resultado_no_viables_porcentaje = CASE
        WHEN COALESCE(r.semillas_viables, 0) + COALESCE(r.semillas_no_viables, 0) > 0
        THEN ROUND(COALESCE(r.semillas_no_viables, 0) * 100.0 / (r.semillas_viables + r.semillas_no_viables), 2)
        ELSE NULL
    END;
