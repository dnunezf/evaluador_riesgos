-- 1. TA - Tarea / Actividad
CREATE TABLE TA (
  Id INT AUTO_INCREMENT,
  Nombre VARCHAR(100) NOT NULL,
  CONSTRAINT PK_TA PRIMARY KEY (Id)
);

-- 2. Norma
CREATE TABLE Norma (
  Id INT AUTO_INCREMENT,
  Nombre VARCHAR(100) NOT NULL,
  CONSTRAINT PK_Norma PRIMARY KEY (Id)
);

-- 3. RE - Requisito
CREATE TABLE RE (
  Id INT AUTO_INCREMENT,
  Nombre VARCHAR(200) NOT NULL,
  Texto TEXT NOT NULL,
  NormaId INT NOT NULL,
  CONSTRAINT PK_RE PRIMARY KEY (Id),
  CONSTRAINT FK_RE_Norma FOREIGN KEY (NormaId) REFERENCES Norma(Id) ON DELETE CASCADE
);

-- 4. TR - Tarea_Requisito (JOIN TA ↔ RE)
CREATE TABLE TR (
  TAId INT NOT NULL,
  REId INT NOT NULL,
  CONSTRAINT PK_TR PRIMARY KEY (TAId, REId),
  CONSTRAINT FK_TR_TA FOREIGN KEY (TAId) REFERENCES TA(Id) ON DELETE CASCADE,
  CONSTRAINT FK_TR_RE FOREIGN KEY (REId) REFERENCES RE(Id) ON DELETE CASCADE
);

-- 5. RI - Riesgo
CREATE TABLE RI (
  Id INT AUTO_INCREMENT,
  Nombre VARCHAR(100) NOT NULL,
  Tipo ENUM('Confidencialidad','Integridad','Disponibilidad') NOT NULL,
  CONSTRAINT PK_RI PRIMARY KEY (Id),
  CONSTRAINT CK_RI_Tipo CHECK (Tipo IN ('Confidencialidad','Integridad','Disponibilidad'))
);

-- 6. Re_Ri - Requisito_Riesgo (JOIN RE ↔ RI)
CREATE TABLE Re_Ri (
  REId INT NOT NULL,
  RIId INT NOT NULL,
  CONSTRAINT PK_Re_Ri PRIMARY KEY (REId, RIId),
  CONSTRAINT FK_Re_Ri_RE FOREIGN KEY (REId) REFERENCES RE(Id) ON DELETE CASCADE,
  CONSTRAINT FK_Re_Ri_RI FOREIGN KEY (RIId) REFERENCES RI(Id) ON DELETE CASCADE
);

-- Insertar tareas (TA)
INSERT INTO TA (Id, Nombre) VALUES (1, 'Respaldos y recuperación de bases de datos');
INSERT INTO TA (Id, Nombre) VALUES (2, 'Gestión de accesos a la base de datos');
INSERT INTO TA (Id, Nombre) VALUES (3, 'Aplicación de parches y actualizaciones');
INSERT INTO TA (Id, Nombre) VALUES (4, 'Monitoreo y auditoría de actividades');
INSERT INTO TA (Id, Nombre) VALUES (5, 'Configuración segura de la base de datos');
INSERT INTO TA (Id, Nombre) VALUES (6, 'Gestión del rendimiento y disponibilidad');
INSERT INTO TA (Id, Nombre) VALUES (7, 'Clasificación y protección de datos sensibles');
INSERT INTO TA (Id, Nombre) VALUES (8, 'Gestión de cuentas privilegiadas');
INSERT INTO TA (Id, Nombre) VALUES (9, 'Exportación e importación de datos');
INSERT INTO TA (Id, Nombre) VALUES (10, 'Pruebas en ambientes no productivos');
INSERT INTO TA (Id, Nombre) VALUES (11, 'Eliminación segura de datos y logs');

-- Insertar norma
INSERT INTO Norma (Id, Nombre) VALUES (1, 'ISO/IEC 27002:2005');

-- Insertar riesgos (RI)
INSERT INTO RI (Id, Nombre, Tipo) VALUES (1, 'Pérdida de confidencialidad', 'Confidencialidad');
INSERT INTO RI (Id, Nombre, Tipo) VALUES (2, 'Pérdida de integridad', 'Integridad');
INSERT INTO RI (Id, Nombre, Tipo) VALUES (3, 'Pérdida de disponibilidad', 'Disponibilidad');

-- Insertar requisitos (RE)
-- RE1 - Gestión de copias de respaldo
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (16, 'Gestión de copias de respaldo', '¿Existe una política formal y vigente de respaldos?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (17, 'Gestión de copias de respaldo', '¿Se ejecutan respaldos conforme a un calendario definido (completos/incrementales)?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (18, 'Gestión de copias de respaldo', '¿Se han realizado pruebas de restauración en los últimos 6 meses?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (19, 'Gestión de copias de respaldo', '¿Los respaldos están cifrados y con control de acceso?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (20, 'Gestión de copias de respaldo', '¿Se conserva al menos una copia fuera del sitio o en nube independiente?', 1);

-- RE2 - Gestión de acceso
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (21, 'Gestión de acceso', '¿Hay una política de control de accesos aprobada?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (22, 'Gestión de acceso', '¿Las altas, bajas y cambios de acceso se registran y autorizan?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (23, 'Gestión de acceso', '¿Se aplican principios de mínimo privilegio y necesidad de saber?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (24, 'Gestión de acceso', '¿Se revisan accesos de usuarios al menos trimestralmente?', 1);

-- RE3 - Gestión de identidad y autenticación
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (25, 'Gestión de identidad y autenticación', '¿Cada usuario tiene un identificador único (no compartido)?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (26, 'Gestión de identidad y autenticación', '¿Se exige autenticación fuerte (por ejemplo, MFA) para accesos administrativos o remotos?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (27, 'Gestión de identidad y autenticación', '¿Existe caducidad y complejidad mínima de contraseñas?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (28, 'Gestión de identidad y autenticación', '¿Se bloquean cuentas tras múltiples intentos fallidos?', 1);

-- RE4 - Gestión de vulnerabilidades técnicas
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (29, 'Gestión de vulnerabilidades técnicas', '¿Se ejecutan escaneos de vulnerabilidades con una periodicidad definida?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (30, 'Gestión de vulnerabilidades técnicas', '¿Existe un proceso para evaluar y priorizar vulnerabilidades (CVSS u otro)?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (31, 'Gestión de vulnerabilidades técnicas', '¿Las vulnerabilidades críticas se corrigen dentro de plazos definidos?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (32, 'Gestión de vulnerabilidades técnicas', '¿Se validan las correcciones con re-escaneos?', 1);

-- RE5 - Gestión de cambios
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (33, 'Gestión de cambios', '¿Todo cambio pasa por un procedimiento formal (solicitud, evaluación, aprobación)?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (34, 'Gestión de cambios', '¿Se prueban parches en ambientes no productivos antes de producción?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (35, 'Gestión de cambios', '¿Se tiene un plan de reversión documentado por cambio?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (36, 'Gestión de cambios', '¿Se registran fecha, responsable y resultado de cada cambio?', 1);

-- RE6 - Registros de auditoría
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (37, 'Registros de auditoría', '¿El sistema genera logs de seguridad y auditoría suficientes (accesos, cambios, fallos)?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (38, 'Registros de auditoría', '¿Los logs están protegidos contra borrado o alteración no autorizada?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (39, 'Registros de auditoría', '¿Existe retención mínima definida para logs?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (40, 'Registros de auditoría', '¿Se revisan los logs de seguridad de forma periódica?', 1);

-- RE7 - Monitoreo de actividades
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (41, 'Monitoreo de actividades', '¿Existe monitoreo continuo o programado de eventos de seguridad?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (42, 'Monitoreo de actividades', '¿Hay alertas configuradas para actividades anómalas o privilegios?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (43, 'Monitoreo de actividades', '¿Se investigan y documentan las alertas relevantes?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (44, 'Monitoreo de actividades', '¿Se miden tiempos de detección y respuesta?', 1);

-- RE8 - Seguridad en la configuración de sistemas
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (45, 'Seguridad en la configuración de sistemas', '¿Se aplican benchmarks de configuración segura (por ejemplo, CIS) al DBMS y SO?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (46, 'Seguridad en la configuración de sistemas', '¿Se eliminan o deshabilitan cuentas y servicios por defecto?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (47, 'Seguridad en la configuración de sistemas', '¿Se gestiona de forma segura la configuración (versionado y aprobaciones)?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (48, 'Seguridad en la configuración de sistemas', '¿Se validan configuraciones con chequeos periódicos (hardening checks)?', 1);

-- RE9 - Resiliencia de los sistemas
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (49, 'Resiliencia de los sistemas', '¿Existe un plan documentado de continuidad/recuperación para la BD?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (50, 'Resiliencia de los sistemas', '¿Se han probado los procedimientos de recuperación en el último año?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (51, 'Resiliencia de los sistemas', '¿Se cuenta con alta disponibilidad o mecanismos de failover?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (52, 'Resiliencia de los sistemas', '¿Se definen RPO/RTO y se cumplen en pruebas?', 1);

-- RE10 - Clasificación de la información
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (53, 'Clasificación de la información', '¿Los datos están clasificados por sensibilidad (p. ej., público, interno, confidencial)?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (54, 'Clasificación de la información', '¿La clasificación define controles mínimos por nivel (cifrado, acceso, retención)?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (55, 'Clasificación de la información', '¿Los sistemas y tablas con datos sensibles están identificados?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (56, 'Clasificación de la información', '¿El personal conoce y aplica la clasificación?', 1);

-- RE11 - Gestión
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (57, 'Gestión de accesos privilegiados', '¿Las cuentas privilegiadas están justificadas y aprobadas formalmente?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (58, 'Gestión de accesos privilegiados', '¿Se usa PAM o controles equivalentes para sesiones privilegiadas?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (59, 'Gestión de accesos privilegiados', '¿Se registran y auditan las acciones de usuarios privilegiados?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (60, 'Gestión de accesos privilegiados', '¿El acceso privilegiado requiere MFA?', 1);

-- RE12 - Transferencia segura de información
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (61, 'Transferencia segura de información', '¿Las transferencias usan canales cifrados (TLS, SFTP, VPN)?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (62, 'Transferencia segura de información', '¿Se autentican las partes involucradas antes de la transferencia?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (63, 'Transferencia segura de información', '¿Hay controles contra fuga de datos en exportaciones (DLP, enmascarado)?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (64, 'Transferencia segura de información', '¿Se registran y revisan transferencias de datos sensibles?', 1);

-- RE13 - Aislamiento de ambientes de prueba
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (65, 'Aislamiento de ambientes de prueba', '¿Los ambientes de prueba y producción están segregados lógicamente y/o físicamente?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (66, 'Aislamiento de ambientes de prueba', '¿Se prohíbe usar datos productivos sin anonimización/enmascaramiento en pruebas?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (67, 'Aislamiento de ambientes de prueba', '¿Las credenciales de prueba son distintas a las de producción?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (68, 'Aislamiento de ambientes de prueba', '¿Los accesos entre ambientes están controlados y registrados?', 1);

-- RE14 - Eliminación segura de información
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (69, 'Eliminación segura de información', '¿Existe una política de retención y eliminación de datos/logs?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (70, 'Eliminación segura de información', '¿Se aplican métodos de borrado seguro o destrucción certificada cuando corresponde?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (71, 'Eliminación segura de información', '¿Se eliminan oportunamente datos que exceden su período de retención?', 1);
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES (72, 'Eliminación segura de información', '¿Se conserva evidencia de la eliminación (actas, bitácoras)?', 1);


-- Insertar relaciones Tarea ↔ Requisito (TR)
-- RE1 - Gestión de copias de respaldo → TA 1 (Respaldos y recuperación)
INSERT INTO TR (TAId, REId) VALUES (1, 16);
INSERT INTO TR (TAId, REId) VALUES (1, 17);
INSERT INTO TR (TAId, REId) VALUES (1, 18);
INSERT INTO TR (TAId, REId) VALUES (1, 19);
INSERT INTO TR (TAId, REId) VALUES (1, 20);

-- RE2 - Gestión de acceso → TA 2 (Gestión de accesos a la base de datos)
INSERT INTO TR (TAId, REId) VALUES (2, 21);
INSERT INTO TR (TAId, REId) VALUES (2, 22);
INSERT INTO TR (TAId, REId) VALUES (2, 23);
INSERT INTO TR (TAId, REId) VALUES (2, 24);

-- RE3 - Gestión de identidad y autenticación → TA 2 (Gestión de accesos)
INSERT INTO TR (TAId, REId) VALUES (2, 25);
INSERT INTO TR (TAId, REId) VALUES (2, 26);
INSERT INTO TR (TAId, REId) VALUES (2, 27);
INSERT INTO TR (TAId, REId) VALUES (2, 28);

-- RE4 - Gestión de vulnerabilidades técnicas → TA 3 (Aplicación de parches y actualizaciones)
INSERT INTO TR (TAId, REId) VALUES (3, 29);
INSERT INTO TR (TAId, REId) VALUES (3, 30);
INSERT INTO TR (TAId, REId) VALUES (3, 31);
INSERT INTO TR (TAId, REId) VALUES (3, 32);

-- RE5 - Gestión de cambios → TA 3 (Aplicación de parches y actualizaciones)
INSERT INTO TR (TAId, REId) VALUES (3, 33);
INSERT INTO TR (TAId, REId) VALUES (3, 34);
INSERT INTO TR (TAId, REId) VALUES (3, 35);
INSERT INTO TR (TAId, REId) VALUES (3, 36);

-- RE6 - Registros de auditoría → TA 4 (Monitoreo y auditoría)
INSERT INTO TR (TAId, REId) VALUES (4, 37);
INSERT INTO TR (TAId, REId) VALUES (4, 38);
INSERT INTO TR (TAId, REId) VALUES (4, 39);
INSERT INTO TR (TAId, REId) VALUES (4, 40);

-- RE7 - Monitoreo de actividades → TA 4 (Monitoreo y auditoría)
INSERT INTO TR (TAId, REId) VALUES (4, 41);
INSERT INTO TR (TAId, REId) VALUES (4, 42);
INSERT INTO TR (TAId, REId) VALUES (4, 43);
INSERT INTO TR (TAId, REId) VALUES (4, 44);

-- RE8 - Seguridad en la configuración de sistemas → TA 5 (Configuración segura de la base de datos)
INSERT INTO TR (TAId, REId) VALUES (5, 45);
INSERT INTO TR (TAId, REId) VALUES (5, 46);
INSERT INTO TR (TAId, REId) VALUES (5, 47);
INSERT INTO TR (TAId, REId) VALUES (5, 48);

-- RE9 - Resiliencia de los sistemas → TA 6 (Gestión del rendimiento y disponibilidad)
INSERT INTO TR (TAId, REId) VALUES (6, 49);
INSERT INTO TR (TAId, REId) VALUES (6, 50);
INSERT INTO TR (TAId, REId) VALUES (6, 51);
INSERT INTO TR (TAId, REId) VALUES (6, 52);

-- RE10 - Clasificación de la información → TA 7 (Clasificación y protección de datos sensibles)
INSERT INTO TR (TAId, REId) VALUES (7, 53);
INSERT INTO TR (TAId, REId) VALUES (7, 54);
INSERT INTO TR (TAId, REId) VALUES (7, 55);
INSERT INTO TR (TAId, REId) VALUES (7, 56);

-- RE11 - Gestión de accesos privilegiados → TA 8 (Gestión de cuentas privilegiadas)
INSERT INTO TR (TAId, REId) VALUES (8, 57);
INSERT INTO TR (TAId, REId) VALUES (8, 58);
INSERT INTO TR (TAId, REId) VALUES (8, 59);
INSERT INTO TR (TAId, REId) VALUES (8, 60);

-- RE12 - Transferencia segura de información → TA 9 (Exportación e importación de datos)
INSERT INTO TR (TAId, REId) VALUES (9, 61);
INSERT INTO TR (TAId, REId) VALUES (9, 62);
INSERT INTO TR (TAId, REId) VALUES (9, 63);
INSERT INTO TR (TAId, REId) VALUES (9, 64);

-- RE13 - Aislamiento de ambientes de prueba → TA 10 (Pruebas en ambientes no productivos)
INSERT INTO TR (TAId, REId) VALUES (10, 65);
INSERT INTO TR (TAId, REId) VALUES (10, 66);
INSERT INTO TR (TAId, REId) VALUES (10, 67);
INSERT INTO TR (TAId, REId) VALUES (10, 68);

-- RE14 - Eliminación segura de información → TA 11 (Eliminación segura de datos y logs)
INSERT INTO TR (TAId, REId) VALUES (11, 69);
INSERT INTO TR (TAId, REId) VALUES (11, 70);
INSERT INTO TR (TAId, REId) VALUES (11, 71);
INSERT INTO TR (TAId, REId) VALUES (11, 72);

-- Insertar relaciones Requisito ↔ Riesgo (Re_Ri)
-- RE1 - Gestión de copias de respaldo
INSERT INTO Re_Ri (REId, RIId) VALUES (16, 3), (16, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (17, 3), (17, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (18, 3), (18, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (19, 1), (19, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (20, 3), (20, 2);

-- RE2 - Gestión de acceso
INSERT INTO Re_Ri (REId, RIId) VALUES (21, 1), (21, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (22, 2), (22, 1);
INSERT INTO Re_Ri (REId, RIId) VALUES (23, 1), (23, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (24, 1), (24, 2);

-- RE3 - Gestión de identidad y autenticación
INSERT INTO Re_Ri (REId, RIId) VALUES (25, 1), (25, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (26, 1), (26, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (27, 1), (27, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (28, 1), (28, 2);

-- RE4 - Gestión de vulnerabilidades técnicas
INSERT INTO Re_Ri (REId, RIId) VALUES (29, 2), (29, 3);
INSERT INTO Re_Ri (REId, RIId) VALUES (30, 2), (30, 3);
INSERT INTO Re_Ri (REId, RIId) VALUES (31, 2), (31, 3);
INSERT INTO Re_Ri (REId, RIId) VALUES (32, 2), (32, 3);

-- RE5 - Gestión de cambios
INSERT INTO Re_Ri (REId, RIId) VALUES (33, 2), (33, 3);
INSERT INTO Re_Ri (REId, RIId) VALUES (34, 2), (34, 3);
INSERT INTO Re_Ri (REId, RIId) VALUES (35, 3), (35, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (36, 2), (36, 3);

-- RE6 - Registros de auditoría
INSERT INTO Re_Ri (REId, RIId) VALUES (37, 2), (37, 1);
INSERT INTO Re_Ri (REId, RIId) VALUES (38, 2), (38, 1);
INSERT INTO Re_Ri (REId, RIId) VALUES (39, 2), (39, 3);
INSERT INTO Re_Ri (REId, RIId) VALUES (40, 2), (40, 1);

-- RE7 - Monitoreo de actividades
INSERT INTO Re_Ri (REId, RIId) VALUES (41, 2), (41, 1);
INSERT INTO Re_Ri (REId, RIId) VALUES (42, 2), (42, 1);
INSERT INTO Re_Ri (REId, RIId) VALUES (43, 2), (43, 1);
INSERT INTO Re_Ri (REId, RIId) VALUES (44, 3), (44, 2);

-- RE8 - Seguridad en la configuración de sistemas
INSERT INTO Re_Ri (REId, RIId) VALUES (45, 2), (45, 1);
INSERT INTO Re_Ri (REId, RIId) VALUES (46, 1), (46, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (47, 2), (47, 1);
INSERT INTO Re_Ri (REId, RIId) VALUES (48, 2), (48, 1);

-- RE9 - Resiliencia de los sistemas
INSERT INTO Re_Ri (REId, RIId) VALUES (49, 3), (49, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (50, 3), (50, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (51, 3), (51, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (52, 3), (52, 2);

-- RE10 - Clasificación de la información
INSERT INTO Re_Ri (REId, RIId) VALUES (53, 1), (53, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (54, 1), (54, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (55, 1), (55, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (56, 1), (56, 2);

-- RE11 - Gestión de accesos privilegiados
INSERT INTO Re_Ri (REId, RIId) VALUES (57, 1), (57, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (58, 1), (58, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (59, 2), (59, 1);
INSERT INTO Re_Ri (REId, RIId) VALUES (60, 1), (60, 2);

-- RE12 - Transferencia segura de información
INSERT INTO Re_Ri (REId, RIId) VALUES (61, 1), (61, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (62, 1), (62, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (63, 1), (63, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (64, 1), (64, 2);

-- RE13 - Aislamiento de ambientes de prueba
INSERT INTO Re_Ri (REId, RIId) VALUES (65, 1), (65, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (66, 1), (66, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (67, 1), (67, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (68, 1), (68, 2);

-- RE14 - Eliminación segura de información
INSERT INTO Re_Ri (REId, RIId) VALUES (69, 1), (69, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (70, 1), (70, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (71, 1), (71, 2);
INSERT INTO Re_Ri (REId, RIId) VALUES (72, 2), (72, 1);

-- ======================================================
-- NORMA COBIT 4.1
-- ======================================================
INSERT INTO Norma (Id, Nombre) VALUES (2, 'COBIT 4.1');

-- ======================================================
-- REQUISITOS / PREGUNTAS (RE) - COBIT 4.1  (Ids desde 2001)
-- ======================================================

-- PO2
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2001,'PO2','PO2.4 Administración de Integridad: ¿Se implementan procedimientos para garantizar la integridad y consistencia de los datos almacenados en respaldos?',2),
(2002,'PO2','PO2.1 Modelo de Arquitectura de Información: ¿El modelo de información empresarial está diseñado para optimizar el rendimiento del sistema al asegurar que los datos estén bien estructurados y accesibles?',2),
(2003,'PO2','PO2.3 Esquema de Clasificación de Datos: ¿Existe un esquema de clasificación de datos que determine los niveles de seguridad adecuados según la sensibilidad?',2),
(2004,'PO2','PO2.1 Modelo de Arquitectura de Información: ¿El modelo de información facilita la gestión eficiente de la capacidad y el almacenamiento?',2),
(2005,'PO2','PO2.4 Administración de Integridad: ¿Se asegura la integridad y consistencia de los datos durante actualizaciones y mantenimiento del DBMS?',2);

-- PO8
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2006,'PO8','PO8.3 Estándares de Desarrollo/Adquisición: ¿Se adoptan estándares específicos para respaldo y recuperación que garanticen eficacia y calidad?',2),
(2007,'PO8','PO8.2 Estándares y Prácticas de Calidad: ¿Existen estándares/prácticas de calidad que guíen el monitoreo y optimización del rendimiento?',2),
(2008,'PO8','PO8.1 Sistema de Gestión de Calidad: ¿El QMS incluye procesos que aseguran la seguridad e integridad de los datos almacenados?',2),
(2009,'PO8','PO8.2 Estándares y Prácticas de Calidad: ¿Existen estándares que guíen la gestión de capacidad y almacenamiento?',2),
(2010,'PO8','PO8.5 Mejora Continua: ¿Se promueve la mejora continua durante las actualizaciones y mantenimiento del DBMS mediante un plan de calidad?',2);

-- PO9
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2011,'PO9','PO9.4 Evaluación de Riesgos de TI: ¿Se evalúan recurrentemente los riesgos de respaldo y recuperación (probabilidad/impacto)?',2),
(2012,'PO9','PO9.6 Plan de Acción de Riesgos: ¿Se monitorean los planes de acción para riesgos ligados a la optimización del rendimiento?',2),
(2013,'PO9','PO9.3 Identificación de Eventos: ¿Se identifican y registran eventos que afecten la seguridad de la base de datos (amenazas/vulnerabilidades)?',2),
(2014,'PO9','PO9.5 Respuesta a los Riesgos: ¿Se desarrollan y mantienen procesos de respuesta a riesgos para mitigar riesgos de seguridad de la BD?',2),
(2015,'PO9','PO9.1 Marco de Gestión de Riesgos: ¿El marco incluye la gestión de riesgos asociados con capacidad y almacenamiento?',2),
(2016,'PO9','PO9.6 Plan de Acción de Riesgos: ¿Se priorizan y planifican controles para responder a riesgos en actualización y mantenimiento del DBMS?',2);

-- A12
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2017,'A12','A12.3 Control y Auditabilidad: ¿Se implementan controles para que respaldo/recuperación sea exacto, completo, oportuno, autorizado y auditable?',2),
(2018,'A12','A12.5 Configuración/Implantación SW Adquirido: ¿Se configuran e implementan adecuadamente las aplicaciones para asegurar rendimiento óptimo?',2),
(2019,'A12','A12.4 Seguridad/Disponibilidad de Aplicaciones: ¿Se abordan los requerimientos de seguridad y disponibilidad para proteger la BD?',2),
(2020,'A12','A12.2 Diseño Detallado: ¿Se preparan/ aprueban diseños y requerimientos técnicos para cumplir necesidades de capacidad/almacenamiento?',2),
(2021,'A12','A12.6 Actualizaciones Relevantes: ¿Se sigue un proceso formal para actualizaciones significativas del DBMS?',2),
(2022,'A12','A12.10 Mantenimiento SW: ¿Se desarrolla y sigue una estrategia/plan de mantenimiento del DBMS?',2);

-- A13
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2023,'A13','A13.2 Protección/Disponibilidad Infraestructura: ¿Se implementan controles para proteger recursos y asegurar disponibilidad para respaldo/recuperación?',2),
(2024,'A13','A13.4 Ambiente de Prueba de Factibilidad: ¿Se establece un ambiente de pruebas que evite impactos negativos en rendimiento?',2),
(2025,'A13','A13.2 Seguridad Infraestructura: ¿Se implementan medidas adecuadas de seguridad para proteger la infraestructura y la BD?',2),
(2026,'A13','A13.1 Plan de Adquisición de Infraestructura: ¿El plan considera necesidades futuras de capacidad/almacenamiento y riesgos?',2),
(2027,'A13','A13.3 Mantenimiento de Infraestructura: ¿Existe una estrategia/plan de mantenimiento (parches/actualizaciones/riesgos) para el DBMS?',2);

-- A14
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2028,'A14','A14.1 Plan de Operaciones: ¿Se documentan procedimientos de administración, respaldo y recuperación para operación correcta?',2),
(2029,'A14','A14.4 Transferencia de Conocimiento a Operaciones: ¿Se transfiere conocimiento al soporte para operación eficiente y monitoreo del rendimiento?',2),
(2030,'A14','A14.2 Conocimiento a Gerencia: ¿Se transfiere a la gerencia conocimiento sobre seguridad física, control de acceso y procesos de seguridad de BD?',2),
(2031,'A14','A14.1 Plan de Operaciones: ¿El plan incluye documentación de capacidad operativa y niveles de servicio para gestionar capacidad/almacenamiento?',2),
(2032,'A14','A14.3 Conocimiento a Usuarios Finales: ¿Se brinda entrenamiento y actualizaciones de documentación para apoyar operación y mantenimiento del DBMS?',2);

-- A16
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2033,'A16','A16.5 Cierre y Documentación del Cambio: ¿Se actualiza la documentación de respaldo/recuperación tras cambios?',2),
(2034,'A16','A16.4 Seguimiento/Reporte de Cambios: ¿Se sigue y reporta el estatus de cambios para asegurar monitoreo/optimización del rendimiento?',2),
(2035,'A16','A16.2 Evaluación/Autorización de Cambios: ¿Se evalúan, priorizan y autorizan los cambios para prevenir impactos en seguridad de BD?',2),
(2036,'A16','A16.1 Estándares/Procedimientos de Cambios: ¿Existen procedimientos formales para manejar cambios y asegurar adecuada gestión de capacidad/almacenamiento?',2),
(2037,'A16','A16.3 Cambios de Emergencia: ¿Existe proceso para gestionar cambios de emergencia (documentación y pruebas) manteniendo el DBMS óptimo?',2);

-- A17
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2038,'A17','A17.5 Conversión de Sistemas/Datos: ¿El plan de conversión incluye respaldo y vuelta atrás para garantizar integridad y recuperación?',2),
(2039,'A17','A17.6 Pruebas de Cambios: ¿Se realizan pruebas de seguridad y rendimiento antes de pasar a operación?',2),
(2040,'A17','A17.4 Ambiente de Prueba: ¿Se establece un entorno de prueba seguro y representativo para mantener controles de seguridad?',2),
(2041,'A17','A17.8 Promoción a Producción: ¿Se controla la entrega a producción y ejecución paralela para evaluar capacidad y desempeño?',2),
(2042,'A17','A17.7 Prueba de Aceptación Final: ¿Se realiza prueba de aceptación final (errores significativos/regresión) antes de producción?',2);

-- DS1
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2043,'DS1','DS1.5 Monitoreo/Reporte SLA: ¿Se monitorea y reporta el cumplimiento de SLA para asegurar respaldo y recuperación según acuerdos?',2),
(2044,'DS1','DS1.5 Monitoreo/Reporte SLA: ¿Se monitorea continuamente el rendimiento de la BD y se reportan resultados para optimización?',2),
(2045,'DS1','DS1.1 Marco de SLA: ¿El marco define y gestiona niveles de seguridad para bases de datos?',2),
(2046,'DS1','DS1.3 Acuerdos de Nivel de Servicio: ¿Incluyen métricas/compromisos de capacidad y almacenamiento de BD?',2),
(2047,'DS1','DS1.6 Revisión de SLA/Contratos: ¿Se revisan regularmente los SLA para alinear actualizaciones y mantenimiento del DBMS al negocio?',2);

-- DS2
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2048,'DS2','DS2.3 Riesgos del Proveedor: ¿Se identifican y mitigan riesgos de proveedores de respaldo para cumplir seguridad y continuidad?',2),
(2049,'DS2','DS2.4 Desempeño del Proveedor: ¿Se monitora el desempeño de proveedores respecto a optimización del rendimiento de BD?',2),
(2050,'DS2','DS2.3 Seguridad de Proveedores: ¿Se asegura que proveedores de seguridad cumplen requisitos y acuerdos de confidencialidad?',2),
(2051,'DS2','DS2.2 Gestión de Relaciones: ¿Se formaliza la gestión con proveedores para cumplir capacidad y almacenamiento?',2),
(2052,'DS2','DS2.1 Identificación de Proveedores: ¿Se documentan relaciones de actualización/mantenimiento del DBMS (roles, responsabilidades, entregables)?',2);

-- DS5
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2053,'DS5','DS5.5 Pruebas/Monitoreo de Seguridad: ¿Se prueba y monitorea respaldo/recuperación para efectividad ante incidentes de seguridad?',2),
(2054,'DS5','DS5.5 Monitoreo de Rendimiento: ¿Se monitorea el rendimiento para asegurar que controles de seguridad no afecten desempeño?',2),
(2055,'DS5','DS5.3 Gestión de Identidad: ¿Todos los usuarios de BD están identificados y autenticados adecuadamente?',2),
(2056,'DS5','DS5.4 Gestión de Cuentas: ¿Se gestionan cuentas/privilegios para garantizar seguridad de la BD?',2),
(2057,'DS5','DS5.3 Identidad y Capacidad: ¿La gestión de identidades/permiso optimiza uso de capacidad/almacenamiento?',2),
(2058,'DS5','DS5.8 Llaves Criptográficas: ¿Se gestionan correctamente las llaves durante actualizaciones/mantenimiento del DBMS?',2),
(2059,'DS5','DS5.9 Antimalware: ¿Se implementan medidas preventivas/detectivas/correctivas contra malware durante actualizaciones/mantenimiento?',2);

-- DS11
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2060,'DS11','DS11.5 Respaldo/Restauración: ¿Existen procedimientos efectivos para disponibilidad y recuperación según negocio?',2),
(2061,'DS11','DS11.1 Requisitos de Negocio Datos: ¿Se procesan completamente y a tiempo para rendimiento óptimo?',2),
(2062,'DS11','DS11.6 Requisitos de Seguridad de Datos: ¿Se definen políticas/procedimientos para seguridad en recepción, procesamiento, almacenamiento y salida?',2),
(2063,'DS11','DS11.4 Eliminación: ¿Se implementan procedimientos adecuados para eliminación/transferencia protegiendo datos sensibles?',2),
(2064,'DS11','DS11.2 Archivo/Retención: ¿Se definen procedimientos de archivo, almacenamiento y retención cumpliendo negocio/regulación?',2),
(2065,'DS11','DS11.3 Librería de Medios: ¿Se mantiene inventario de medios almacenados/archivados asegurando usabilidad e integridad?',2),
(2066,'DS11','DS11.1 Requisitos de Negocio Datos: ¿Se garantiza procesamiento completo y que el sistema se actualiza/mantiene según negocio?',2);

-- DS12
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2067,'DS12','DS12.1 Selección/Diseño DC: ¿Se seleccionan DC adecuados para respaldos/recuperación considerando riesgos y regulaciones?',2),
(2068,'DS12','DS12.4 Protección Ambiental: ¿Se implementan medidas/monitoreo de ambiente físico para operación óptima?',2),
(2069,'DS12','DS12.2 Seguridad Física: ¿Se definen medidas físicas (perímetro/ubicación de equipos) para proteger BD e información?',2),
(2070,'DS12','DS12.3 Acceso Físico: ¿Se definen procedimientos para otorgar/limitar/revocar acceso físico a áreas críticas (justificado, autorizado, registrado, monitoreado)?',2),
(2071,'DS12','DS12.5 Administración de Instalaciones: ¿Se administra equipo de comunicaciones y energía conforme a leyes y requisitos?',2),
(2072,'DS12','DS12.1 Selección/Diseño DC: ¿El DC soporta operaciones y actualizaciones del DBMS sin interrupciones?',2);

-- DS13
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2073,'DS13','DS13.5 Mantenimiento Preventivo HW: ¿Existen procedimientos para mantener HW de respaldo/recuperación y reducir fallas?',2),
(2074,'DS13','DS13.3 Monitoreo Infraestructura TI: ¿Se monitorea infraestructura y se almacenan registros para analizar desempeño?',2),
(2075,'DS13','DS13.4 Documentos Sensitivos/Salida: ¿Se establecen resguardos para documentos/dispositivos sensitivos garantizando seguridad de la información?',2),
(2076,'DS13','DS13.2 Programación de Tareas: ¿Se organiza/autoriza la programación para maximizar desempeño y uso de recursos de almacenamiento?',2),
(2077,'DS13','DS13.1 Procedimientos de Operación: ¿Se definen procedimientos/instrucciones estándar para operación y mantenimiento del DBMS?',2);

-- ME1
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2078,'ME1','ME1.4 Evaluación del Desempeño: ¿Se compara periódicamente el desempeño de respaldo/recuperación con metas y se corrige?',2),
(2079,'ME1','ME1.1 Enfoque de Monitoreo: ¿Se establece un marco de monitoreo para medir entrega de servicios y optimización del rendimiento de BD?',2),
(2080,'ME1','ME1.5 Reportes a Alta Dirección: ¿Se reporta avance de seguridad de BD (nivel de servicio y contribución a objetivos)?',2),
(2081,'ME1','ME1.2 Datos de Monitoreo: ¿Se definen objetivos/indicadores de capacidad/almacenamiento y se reporta el avance?',2),
(2082,'ME1','ME1.6 Acciones Correctivas: ¿Se identifican/inician acciones correctivas basadas en monitoreo/evaluación del DBMS?',2);

-- ME2
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2083,'ME2','ME2.3 Excepciones de Control: ¿Se identifican/examinan excepciones en respaldo/recuperación y se corrigen?',2),
(2084,'ME2','ME2.1 Monitoreo del Control Interno: ¿Se monitorea y mejora el marco de control de rendimiento de TI?',2),
(2085,'ME2','ME2.4 Autoevaluación: ¿Se realiza autoevaluación continua de controles de seguridad de BD?',2),
(2086,'ME2','ME2.2 Revisiones de Auditoría: ¿Se auditan controles de gestión de capacidad/almacenamiento?',2),
(2087,'ME2','ME2.7 Acciones Correctivas: ¿Se implementan acciones correctivas del control interno para mantenimiento/actualización del DBMS?',2);

-- ME4
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2088,'ME4','ME4.3 Entrega de Valor: ¿Se gestiona la inversión en soluciones de respaldo/recuperación para aportar valor y proteger datos?',2),
(2089,'ME4','ME4.6 Medición del Desempeño: ¿Se confirma cumplimiento de objetivos de monitoreo y optimización del rendimiento de TI?',2),
(2090,'ME4','ME4.5 Administración de Riesgos: ¿Se define el riesgo aceptable de seguridad de BD y se mantiene dentro de límites?',2),
(2091,'ME4','ME4.4 Administración de Recursos: ¿Se revisa inversión/uso/asignación de recursos para capacidad/almacenamiento?',2),
(2092,'ME4','ME4.7 Aseguramiento Independiente: ¿Se asegura conformidad de actualización/mantenimiento del DBMS mediante auditorías?',2);

-- Bloque final sugerido (tareas directas)
INSERT INTO RE (Id, Nombre, Texto, NormaId) VALUES
(2093,'Gestión de accesos a la base de datos','¿Existe un procedimiento formal para altas, bajas y cambios de accesos a la base de datos?',2),
(2094,'Gestión de accesos a la base de datos','¿Se aplican mínimo privilegio y necesidad de saber en la asignación de accesos?',2),
(2095,'Gestión de accesos a la base de datos','¿Se revisan y auditan periódicamente los accesos de usuarios y administradores?',2),
(2096,'Gestión de accesos a la base de datos','¿Se registran y controlan las sesiones con privilegios elevados?',2),
(2097,'Gestión de accesos a la base de datos','¿Se bloquean automáticamente las cuentas inactivas o tras intentos fallidos repetidos?',2),

(2098,'Clasificación y protección de datos sensibles','¿Existe un esquema de clasificación de la información según su sensibilidad?',2),
(2099,'Clasificación y protección de datos sensibles','¿Se aplican controles mínimos (cifrado, acceso, retención) según clasificación?',2),
(2100,'Clasificación y protección de datos sensibles','¿Se identifican y documentan sistemas/tablas con datos sensibles?',2),
(2101,'Clasificación y protección de datos sensibles','¿El personal está capacitado para manejar la información según su clasificación?',2),
(2102,'Clasificación y protección de datos sensibles','¿Se revisa periódicamente la clasificación de los activos de información?',2),

(2103,'Gestión de cuentas privilegiadas','¿Se justifican y aprueban formalmente las cuentas privilegiadas?',2),
(2104,'Gestión de cuentas privilegiadas','¿Se usa PAM o controles equivalentes para accesos privilegiados?',2),
(2105,'Gestión de cuentas privilegiadas','¿Se registran y auditan todas las acciones con privilegios elevados?',2),
(2106,'Gestión de cuentas privilegiadas','¿El acceso privilegiado requiere MFA?',2),
(2107,'Gestión de cuentas privilegiadas','¿Se revisan y revocan permisos innecesarios de cuentas privilegiadas?',2),

(2108,'Exportación e importación de datos','¿Se utilizan canales cifrados (TLS, SFTP, VPN) para transferencias?',2),
(2109,'Exportación e importación de datos','¿Se autentican las partes antes de la transferencia?',2),
(2110,'Exportación e importación de datos','¿Existen controles para prevenir fugas en exportaciones (DLP, enmascarado)?',2),
(2111,'Exportación e importación de datos','¿Se registran y revisan transferencias de datos sensibles?',2),
(2112,'Exportación e importación de datos','¿Se conserva evidencia de exportaciones/importaciones realizadas?',2),

(2113,'Eliminación segura de datos y logs','¿Existe política formal de retención y eliminación de datos/logs?',2),
(2114,'Eliminación segura de datos y logs','¿Se utilizan métodos de borrado seguro o destrucción certificada?',2),
(2115,'Eliminación segura de datos y logs','¿Se eliminan oportunamente los datos que exceden su retención?',2),
(2116,'Eliminación segura de datos y logs','¿Se conserva evidencia documentada de la eliminación?',2),
(2117,'Eliminación segura de datos y logs','¿Se realizan revisiones para verificar cumplimiento de la política de eliminación?',2);

-- ======================================================
-- RELACIONES TAREA ↔ REQUISITO (TR)
-- ======================================================

-- PO2
INSERT INTO TR (TAId, REId) VALUES
(1,2001),(6,2002),(5,2003),(6,2004),(3,2005);

-- PO8
INSERT INTO TR (TAId, REId) VALUES
(1,2006),(6,2007),(5,2008),(6,2009),(3,2010);

-- PO9
INSERT INTO TR (TAId, REId) VALUES
(1,2011),(6,2012),(5,2013),(5,2014),(6,2015),(3,2016);

-- A12
INSERT INTO TR (TAId, REId) VALUES
(1,2017),(6,2018),(5,2019),(6,2020),(3,2021),(3,2022);

-- A13
INSERT INTO TR (TAId, REId) VALUES
(1,2023),(6,2024),(5,2025),(6,2026),(3,2027);

-- A14
INSERT INTO TR (TAId, REId) VALUES
(1,2028),(6,2029),(5,2030),(6,2031),(3,2032);

-- A16
INSERT INTO TR (TAId, REId) VALUES
(1,2033),(6,2034),(5,2035),(6,2036),(3,2037);

-- A17
INSERT INTO TR (TAId, REId) VALUES
(1,2038),(6,2039),(5,2040),(6,2041),(3,2042);

-- DS1
INSERT INTO TR (TAId, REId) VALUES
(1,2043),(6,2044),(5,2045),(6,2046),(3,2047);

-- DS2
INSERT INTO TR (TAId, REId) VALUES
(1,2048),(6,2049),(5,2050),(6,2051),(3,2052);

-- DS5
INSERT INTO TR (TAId, REId) VALUES
(1,2053),(6,2054),(2,2055),(2,2056),(6,2057),(3,2058),(3,2059);

-- DS11
INSERT INTO TR (TAId, REId) VALUES
(1,2060),(6,2061),(5,2062),(11,2063),(6,2064),(6,2065),(3,2066);

-- DS12
INSERT INTO TR (TAId, REId) VALUES
(1,2067),(6,2068),(5,2069),(5,2070),(6,2071),(3,2072);

-- DS13
INSERT INTO TR (TAId, REId) VALUES
(1,2073),(6,2074),(7,2075),(6,2076),(3,2077);

-- ME1
INSERT INTO TR (TAId, REId) VALUES
(1,2078),(6,2079),(5,2080),(6,2081),(3,2082);

-- ME2
INSERT INTO TR (TAId, REId) VALUES
(1,2083),(6,2084),(5,2085),(6,2086),(3,2087);

-- ME4
INSERT INTO TR (TAId, REId) VALUES
(1,2088),(6,2089),(5,2090),(6,2091),(3,2092);

-- Bloque final sugerido
INSERT INTO TR (TAId, REId) VALUES
(2,2093),(2,2094),(2,2095),(2,2096),(2,2097),
(7,2098),(7,2099),(7,2100),(7,2101),(7,2102),
(8,2103),(8,2104),(8,2105),(8,2106),(8,2107),
(9,2108),(9,2109),(9,2110),(9,2111),(9,2112),
(11,2113),(11,2114),(11,2115),(11,2116),(11,2117);

-- ======================================================
-- RELACIONES REQUISITO ↔ RIESGO (Re_Ri)
-- (1=Confidencialidad, 2=Integridad, 3=Disponibilidad)
-- ======================================================

-- PO2
INSERT INTO Re_Ri (REId, RIId) VALUES
(2001,2),(2001,3),
(2002,3),(2002,2),
(2003,1),(2003,2),
(2004,3),
(2005,2),(2005,3);

-- PO8
INSERT INTO Re_Ri (REId, RIId) VALUES
(2006,3),(2006,2),
(2007,3),(2007,2),
(2008,1),(2008,2),
(2009,3),(2009,2),
(2010,3),(2010,2);

-- PO9
INSERT INTO Re_Ri (REId, RIId) VALUES
(2011,3),(2011,2),
(2012,3),(2012,2),
(2013,1),(2013,2),
(2014,1),(2014,2),
(2015,3),
(2016,2),(2016,3);

-- A12
INSERT INTO Re_Ri (REId, RIId) VALUES
(2017,2),(2017,3),
(2018,3),
(2019,1),(2019,3),
(2020,3),
(2021,2),(2021,3),
(2022,3),(2022,2);

-- A13
INSERT INTO Re_Ri (REId, RIId) VALUES
(2023,3),(2023,1),
(2024,3),
(2025,1),(2025,3),
(2026,3),
(2027,3),(2027,2);

-- A14
INSERT INTO Re_Ri (REId, RIId) VALUES
(2028,3),(2028,2),
(2029,3),
(2030,1),(2030,2),
(2031,3),
(2032,3);

-- A16
INSERT INTO Re_Ri (REId, RIId) VALUES
(2033,2),(2033,3),
(2034,3),(2034,2),
(2035,1),(2035,2),
(2036,3),(2036,2),
(2037,3),(2037,2);

-- A17
INSERT INTO Re_Ri (REId, RIId) VALUES
(2038,2),(2038,3),
(2039,2),(2039,3),
(2040,1),(2040,2),
(2041,3),
(2042,2);

-- DS1
INSERT INTO Re_Ri (REId, RIId) VALUES
(2043,3),(2043,2),
(2044,3),
(2045,1),(2045,2),
(2046,3),
(2047,3),(2047,2);

-- DS2
INSERT INTO Re_Ri (REId, RIId) VALUES
(2048,3),(2048,1),
(2049,3),
(2050,1),(2050,2),
(2051,3),
(2052,3),(2052,2);

-- DS5
INSERT INTO Re_Ri (REId, RIId) VALUES
(2053,3),(2053,2),
(2054,3),
(2055,1),(2055,2),
(2056,1),(2056,2),
(2057,3),(2057,2),
(2058,1),(2058,2),
(2059,2),(2059,3);

-- DS11
INSERT INTO Re_Ri (REId, RIId) VALUES
(2060,3),(2060,2),
(2061,3),(2061,2),
(2062,1),(2062,2),
(2063,1),(2063,2),
(2064,3),(2064,2),
(2065,2),(2065,3),
(2066,2),(2066,3);

-- DS12
INSERT INTO Re_Ri (REId, RIId) VALUES
(2067,3),
(2068,3),
(2069,1),(2069,3),
(2070,1),(2070,2),
(2071,3),
(2072,3);

-- DS13
INSERT INTO Re_Ri (REId, RIId) VALUES
(2073,3),
(2074,3),(2074,2),
(2075,1),(2075,2),
(2076,3),
(2077,3),(2077,2);

-- ME1
INSERT INTO Re_Ri (REId, RIId) VALUES
(2078,3),(2078,2),
(2079,3),
(2080,1),(2080,2),
(2081,3),
(2082,3),(2082,2);

-- ME2
INSERT INTO Re_Ri (REId, RIId) VALUES
(2083,3),(2083,2),
(2084,3),
(2085,1),(2085,2),
(2086,3),(2086,2),
(2087,3),(2087,2);

-- ME4
INSERT INTO Re_Ri (REId, RIId) VALUES
(2088,3),(2088,2),
(2089,3),
(2090,1),(2090,2),
(2091,3),
(2092,2),(2092,3);

-- Bloque final sugerido
INSERT INTO Re_Ri (REId, RIId) VALUES
(2093,1),(2093,2),
(2094,1),(2094,2),
(2095,1),(2095,2),
(2096,1),(2096,2),
(2097,1),(2097,2),

(2098,1),
(2099,1),(2099,2),
(2100,1),
(2101,1),
(2102,1),

(2103,1),(2103,2),(2103,3),
(2104,1),(2104,2),(2104,3),
(2105,2),(2105,1),(2105,3),
(2106,1),(2106,2),
(2107,1),(2107,2),

(2108,1),(2108,2),
(2109,1),(2109,2),
(2110,1),(2110,2),
(2111,1),(2111,2),
(2112,2),

(2113,1),(2113,2),
(2114,1),(2114,2),
(2115,1),(2115,2),
(2116,2),(2116,1),
(2117,2);

-- monitor_config: estos son los parámetros del monitor
CREATE TABLE IF NOT EXISTS monitor_config (
  id TINYINT PRIMARY KEY DEFAULT 1,
  critico_pct DECIMAL(5,2) NOT NULL DEFAULT 80.00,   -- umbral %
  delay_seg   INT NOT NULL DEFAULT 5,                -- intervalo en s
  habilitado  TINYINT NOT NULL DEFAULT 1
);

INSERT INTO monitor_config (id) VALUES (1)
ON DUPLICATE KEY UPDATE id=id;

-- consumo_critico: estos son los eventos
CREATE TABLE IF NOT EXISTS consumo_critico (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  hora  TIME NOT NULL,
  proceso VARCHAR(100) NOT NULL,
  usuario VARCHAR(100) NOT NULL,
  sql_text TEXT NOT NULL,
  consumo_pct DECIMAL(5,2) NOT NULL,
  detalles TEXT NULL
);
