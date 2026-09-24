create database IF NOT EXISTS `dbrrsscita`;

use `dbrrsscita`;

create table IF NOT EXISTS `tbperfil` (
  `tbperfilid` int NOT NULL AUTO_INCREMENT,
  `tbubicacionid` int NOT NULL,
  `tbperfilnombre` text NOT NULL,
  `tbperfilcontra` text NOT NULL,
  `tbperfilcorreo` text NOT NULL,
  `tbperfilcambiocontra` TINYINT(1) NOT NULL DEFAULT 0,
  `tbperfilrol` varchar(20) NOT NULL DEFAULT 'cliente',
  `tbperfilactivo` boolean NOT NULL DEFAULT TRUE, 
  PRIMARY KEY (`tbperfilid`)
);

INSERT INTO `tbperfil`
  (`tbperfilid`, `tbubicacionid`, `tbperfilnombre`, `tbperfilcontra`, `tbperfilcorreo`, `tbperfilcambiocontra`, `tbperfilrol`, `tbperfilactivo`)
VALUES
  (1, 1, 'admin', 'B7K2M9R4', 'admin@unamatch.com', 1, 'admin', TRUE),
  (2, 2, 'cliente', 'B7K2M9R4', 'cliente@unamatch.com', 1, 'cliente', TRUE);

CREATE TABLE IF NOT EXISTS `tbubicacion` (
  `tbubicacionid` int NOT NULL AUTO_INCREMENT,
  `tbubicacionprovincia` int NULL,
  `tbubicacioncanton` int NULL,
  `tbubicaciondistrito` int NULL,
  `tbubicacionlongitud` decimal(10, 8) NULL,
  `tbubicacionlatitud` decimal(10, 8) NULL,
  `tbubicacionestado` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbubicacionid`)
);

INSERT INTO `tbubicacion`
  (`tbubicacionid`, `tbubicacionprovincia`, `tbubicacioncanton`, `tbubicaciondistrito`, `tbubicacionlongitud`, `tbubicacionlatitud`)
VALUES
  (1, NULL, NULL, NULL, NULL, NULL),
  (2, NULL, NULL, NULL, NULL, NULL);

CREATE TABLE IF NOT EXISTS `tbprovincia` (
  `tbprovinciaid` int NOT NULL,
  `tbprovincianombre` TEXT NOT NULL,
  `tbprovinciaestado` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbprovinciaid`)
);

CREATE TABLE IF NOT EXISTS `tbcanton` (
  `tbcantonid` int NOT NULL,
  `tbprovinciaid` int NOT NULL,
  `tbcantonnombre` TEXT NOT NULL,
  `tbcantonestado` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbcantonid`)
);

CREATE TABLE IF NOT EXISTS `tbdistrito` (
  `tbdistritoid` int NOT NULL,
  `tbcantonid` int NOT NULL,
  `tbdistritonombre` TEXT NOT NULL,
  `tbdistritoestado` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbdistritoid`)
);

-- PROVINCIAS
INSERT INTO
  `tbprovincia` (`tbprovinciaid`, `tbprovincianombre`)
VALUES
  (1, 'San José'),
  (2, 'Alajuela'),
  (3, 'Cartago'),
  (4, 'Heredia'),
  (5, 'Guanacaste'),
  (6, 'Puntarenas'),
  (7, 'Limón');

-- CANTONES
INSERT INTO
  `tbcanton` (`tbcantonid`, `tbprovinciaid`, `tbcantonnombre`)
VALUES
  (101, 1, 'San José'),
  (102, 1, 'Escazú'),
  (103, 1, 'Desamparados'),
  (104, 1, 'Puriscal'),
  (105, 1, 'Tarrazú'),
  (106, 1, 'Aserrí'),
  (107, 1, 'Mora'),
  (108, 1, 'Goicoechea'),
  (109, 1, 'Santa Ana'),
  (110, 1, 'Alajuelita'),
  (111, 1, 'Vázquez de Coronado'),
  (112, 1, 'Acosta'),
  (113, 1, 'Tibás'),
  (114, 1, 'Moravia'),
  (115, 1, 'Montes de Oca'),
  (116, 1, 'Turrubares'),
  (117, 1, 'Dota'),
  (118, 1, 'Curridabat'),
  (119, 1, 'Pérez Zeledón'),
  (120, 1, 'León Cortés Castro'),
  (201, 2, 'Alajuela'),
  (202, 2, 'San Ramón'),
  (203, 2, 'Grecia'),
  (204, 2, 'San Mateo'),
  (205, 2, 'Atenas'),
  (206, 2, 'Naranjo'),
  (207, 2, 'Palmares'),
  (208, 2, 'Poás'),
  (209, 2, 'Orotina'),
  (210, 2, 'San Carlos'),
  (211, 2, 'Zarcero'),
  (212, 2, 'Sarchí'),
  (213, 2, 'Upala'),
  (214, 2, 'Los Chiles'),
  (215, 2, 'Guatuso'),
  (216, 2, 'Río Cuarto'),
  (301, 3, 'Cartago'),
  (302, 3, 'Paraíso'),
  (303, 3, 'La Unión'),
  (304, 3, 'Jiménez'),
  (305, 3, 'Turrialba'),
  (306, 3, 'Alvarado'),
  (307, 3, 'Oreamuno'),
  (308, 3, 'El Guarco'),
  (401, 4, 'Heredia'),
  (402, 4, 'Barva'),
  (403, 4, 'Santo Domingo'),
  (404, 4, 'Santa Bárbara'),
  (405, 4, 'San Rafael'),
  (406, 4, 'San Isidro'),
  (407, 4, 'Belén'),
  (408, 4, 'Flores'),
  (409, 4, 'San Pablo'),
  (410, 4, 'Sarapiquí'),
  (501, 5, 'Liberia'),
  (502, 5, 'Nicoya'),
  (503, 5, 'Santa Cruz'),
  (504, 5, 'Bagaces'),
  (505, 5, 'Carrillo'),
  (506, 5, 'Cañas'),
  (507, 5, 'Abangares'),
  (508, 5, 'Tilarán'),
  (509, 5, 'Nandayure'),
  (510, 5, 'La Cruz'),
  (511, 5, 'Hojancha'),
  (601, 6, 'Puntarenas'),
  (602, 6, 'Esparza'),
  (603, 6, 'Buenos Aires'),
  (604, 6, 'Montes de Oro'),
  (605, 6, 'Osa'),
  (606, 6, 'Quepos'),
  (607, 6, 'Golfito'),
  (608, 6, 'Coto Brus'),
  (609, 6, 'Parrita'),
  (610, 6, 'Corredores'),
  (611, 6, 'Garabito'),
  (612, 6, 'Monteverde'),
  (613, 6, 'Puerto Jiménez'),
  (701, 7, 'Limón'),
  (702, 7, 'Pococí'),
  (703, 7, 'Siquirres'),
  (704, 7, 'Talamanca'),
  (705, 7, 'Matina'),
  (706, 7, 'Guácimo');

-- DISTRITOS
INSERT INTO
  `tbdistrito` (`tbdistritoid`, `tbcantonid`, `tbdistritonombre`)
VALUES
  (10101, 101, 'Carmen'),
  (10102, 101, 'Merced'),
  (10103, 101, 'Hospital'),
  (10104, 101, 'Catedral'),
  (10105, 101, 'Zapote'),
  (10106, 101, 'San Francisco de Dos Ríos'),
  (10107, 101, 'Uruca'),
  (10108, 101, 'Mata Redonda'),
  (10109, 101, 'Pavas'),
  (10110, 101, 'Hatillo'),
  (10111, 101, 'San Sebastián'),
  (10201, 102, 'Escazú'),
  (10202, 102, 'San Antonio'),
  (10203, 102, 'San Rafael'),
  (10301, 103, 'Desamparados'),
  (10302, 103, 'San Miguel'),
  (10303, 103, 'San Juan de Dios'),
  (10304, 103, 'San Rafael Arriba'),
  (10305, 103, 'San Antonio'),
  (10306, 103, 'Frailes'),
  (10307, 103, 'Patarrá'),
  (10308, 103, 'San Cristobal'),
  (10309, 103, 'Rosario'),
  (10310, 103, 'Damas'),
  (10311, 103, 'San Rafael Abajo'),
  (10312, 103, 'Gravilias'),
  (10313, 103, 'Los Guido'),
  (10401, 104, 'Santiago'),
  (10402, 104, 'Mercedes Sur'),
  (10403, 104, 'Barbacoas'),
  (10404, 104, 'Grifo Alto'),
  (10405, 104, 'San Rafael'),
  (10406, 104, 'Candelarita'),
  (10407, 104, 'Desamparaditos'),
  (10408, 104, 'San Antonio'),
  (10409, 104, 'Chires'),
  (10501, 105, 'San Marcos'),
  (10502, 105, 'San Lorenzo'),
  (10503, 105, 'San Carlos'),
  (10601, 106, 'Aserrí'),
  (10602, 106, 'Tarbaca'),
  (10603, 106, 'Vuelta de Jorco'),
  (10604, 106, 'San Gabriel'),
  (10605, 106, 'Legua'),
  (10606, 106, 'Monterrey'),
  (10607, 106, 'Salitrillos'),
  (10701, 107, 'Colón'),
  (10702, 107, 'Guayabo'),
  (10703, 107, 'Tabarcia'),
  (10704, 107, 'Piedras Negras'),
  (10705, 107, 'Picagres'),
  (10706, 107, 'Jaris'),
  (10707, 107, 'Quitirrisí'),
  (10801, 108, 'Guadalupe'),
  (10802, 108, 'San Francisco'),
  (10803, 108, 'Calle Blancos'),
  (10804, 108, 'Mata de Plátano'),
  (10805, 108, 'Ipís'),
  (10806, 108, 'Rancho Redondo'),
  (10807, 108, 'Purral'),
  (10901, 109, 'Santa Ana'),
  (10902, 109, 'Salitral'),
  (10903, 109, 'Pozos'),
  (10904, 109, 'Uruca'),
  (10905, 109, 'Piedades'),
  (10906, 109, 'Brasil'),
  (11001, 110, 'Alajuelita'),
  (11002, 110, 'San Josecito'),
  (11003, 110, 'San Antonio'),
  (11004, 110, 'Concepción'),
  (11005, 110, 'San Felipe'),
  (11101, 111, 'San Isidro'),
  (11102, 111, 'San Rafael'),
  (11103, 111, 'Dulce Nombre de Jesús'),
  (11104, 111, 'Patalillo'),
  (11105, 111, 'Cascajal'),
  (11201, 112, 'San Ignacio'),
  (11202, 112, 'Guaitil'),
  (11203, 112, 'Palmichal'),
  (11204, 112, 'Cangrejal'),
  (11205, 112, 'Sabanillas'),
  (11301, 113, 'San Juan'),
  (11302, 113, 'Cinco Esquinas'),
  (11303, 113, 'Anselmo Llorente'),
  (11304, 113, 'León XIII'),
  (11305, 113, 'Colima'),
  (11401, 114, 'San Vicente'),
  (11402, 114, 'San Jerónimo'),
  (11403, 114, 'La Trinidad'),
  (11501, 115, 'San Pedro'),
  (11502, 115, 'Sabanilla'),
  (11503, 115, 'Mercedes'),
  (11504, 115, 'San Rafael'),
  (11601, 116, 'San Pablo'),
  (11602, 116, 'San Pedro'),
  (11603, 116, 'San Juan de Mata'),
  (11604, 116, 'San Luis'),
  (11605, 116, 'Carara'),
  (11701, 117, 'Santa María'),
  (11702, 117, 'Jardín'),
  (11703, 117, 'Copey'),
  (11801, 118, 'Curridabat'),
  (11802, 118, 'Granadilla'),
  (11803, 118, 'Sánchez'),
  (11804, 118, 'Tirrases'),
  (11901, 119, 'San Isidro de El General'),
  (11902, 119, 'El General'),
  (11903, 119, 'Daniel Flores'),
  (11904, 119, 'Rivas'),
  (11905, 119, 'San Pedro'),
  (11906, 119, 'Platanares'),
  (11907, 119, 'Pejivalle'),
  (11908, 119, 'Cajón'),
  (11909, 119, 'Barú'),
  (11910, 119, 'Río Nuevo'),
  (11911, 119, 'Páramo'),
  (11912, 119, 'La Amistad'),
  (12001, 120, 'San Pablo'),
  (12002, 120, 'San Andrés'),
  (12003, 120, 'Llano Bonito'),
  (12004, 120, 'San Isidro'),
  (12005, 120, 'Santa Cruz'),
  (12006, 120, 'San Antonio'),
  (20101, 201, 'Alajuela'),
  (20102, 201, 'San José'),
  (20103, 201, 'Carrizal'),
  (20104, 201, 'San Antonio'),
  (20105, 201, 'Guácima'),
  (20106, 201, 'San Isidro'),
  (20107, 201, 'Sabanilla'),
  (20108, 201, 'San Rafael'),
  (20109, 201, 'Río Segundo'),
  (20110, 201, 'Desamparados'),
  (20111, 201, 'Turrúcares'),
  (20112, 201, 'Tambor'),
  (20113, 201, 'Garita'),
  (20114, 201, 'Sarapiquí'),
  (20201, 202, 'San Ramón'),
  (20202, 202, 'Santiago'),
  (20203, 202, 'San Juan'),
  (20204, 202, 'Piedades Norte'),
  (20205, 202, 'Piedades Sur'),
  (20206, 202, 'San Rafael'),
  (20207, 202, 'San Isidro'),
  (20208, 202, 'Ángeles'),
  (20209, 202, 'Alfaro'),
  (20210, 202, 'Volio'),
  (20211, 202, 'Concepción'),
  (20212, 202, 'Zapotal'),
  (20213, 202, 'Peñas Blancas'),
  (20214, 202, 'San Lorenzo'),
  (20301, 203, 'Grecia'),
  (20302, 203, 'San Isidro'),
  (20303, 203, 'San José'),
  (20304, 203, 'San Roque'),
  (20305, 203, 'Tacares'),
  (20307, 203, 'Puente de Piedra'),
  (20308, 203, 'Bolivar'),
  (20401, 204, 'San Mateo'),
  (20402, 204, 'Desmonte'),
  (20403, 204, 'Jesús María'),
  (20404, 204, 'Labrador'),
  (20501, 205, 'Atenas'),
  (20502, 205, 'Jesús'),
  (20503, 205, 'Mercedes'),
  (20504, 205, 'San Isidro'),
  (20505, 205, 'Concepción'),
  (20506, 205, 'San José'),
  (20507, 205, 'Santa Eulalia'),
  (20508, 205, 'Escobal'),
  (20601, 206, 'Naranjo'),
  (20602, 206, 'San Miguel'),
  (20603, 206, 'San José'),
  (20604, 206, 'Cirrí Sur'),
  (20605, 206, 'San Jerónimo'),
  (20606, 206, 'San Juan'),
  (20607, 206, 'El Rosario'),
  (20608, 206, 'Palmitos'),
  (20701, 207, 'Palmares'),
  (20702, 207, 'Zaragoza'),
  (20703, 207, 'Buenos Aires'),
  (20704, 207, 'Santiago'),
  (20705, 207, 'Candelaria'),
  (20706, 207, 'Esquipulas'),
  (20707, 207, 'La Granja'),
  (20801, 208, 'San Pedro'),
  (20802, 208, 'San Juan'),
  (20803, 208, 'San Rafael'),
  (20804, 208, 'Carrillos'),
  (20805, 208, 'Sabana Redonda'),
  (20901, 209, 'Orotina'),
  (20902, 209, 'El Mastate'),
  (20903, 209, 'Hacienda Vieja'),
  (20904, 209, 'Coyolar'),
  (20905, 209, 'La Ceiba'),
  (21001, 210, 'Quesada'),
  (21002, 210, 'Florencia'),
  (21003, 210, 'Buenavista'),
  (21004, 210, 'Aguas Zarcas'),
  (21005, 210, 'Venecia'),
  (21006, 210, 'Pital'),
  (21007, 210, 'La Fortuna'),
  (21008, 210, 'La Tigra'),
  (21009, 210, 'La Palmera'),
  (21010, 210, 'Venado'),
  (21011, 210, 'Cutris'),
  (21012, 210, 'Monterrey'),
  (21013, 210, 'Pocosol'),
  (21101, 211, 'Zarcero'),
  (21102, 211, 'Laguna'),
  (21103, 211, 'Tapesco'),
  (21104, 211, 'Guadalupe'),
  (21105, 211, 'Palmira'),
  (21106, 211, 'Zapote'),
  (21107, 211, 'Brisas'),
  (21201, 212, 'Sarchí Norte'),
  (21202, 212, 'Sarchí Sur'),
  (21203, 212, 'Toro Amarillo'),
  (21204, 212, 'San Pedro'),
  (21205, 212, 'Rodríguez'),
  (21301, 213, 'Upala'),
  (21302, 213, 'Aguas Claras'),
  (21303, 213, 'San José O Pizote'),
  (21304, 213, 'Bijagua'),
  (21305, 213, 'Delicias'),
  (21306, 213, 'Dos Ríos'),
  (21307, 213, 'Yolillal'),
  (21308, 213, 'Canalete'),
  (21401, 214, 'Los Chiles'),
  (21402, 214, 'Caño Negro'),
  (21403, 214, 'El Amparo'),
  (21404, 214, 'San Jorge'),
  (21501, 215, 'San Rafael'),
  (21502, 215, 'Buenavista'),
  (21503, 215, 'Cote'),
  (21504, 215, 'Katira'),
  (21601, 216, 'Río Cuarto'),
  (21602, 216, 'Santa Rita'),
  (21603, 216, 'Santa Isabel'),
  (30101, 301, 'Oriental'),
  (30102, 301, 'Occidental'),
  (30103, 301, 'Carmen'),
  (30104, 301, 'San Nicolás'),
  (30105, 301, 'Aguacaliente o San Francisco'),
  (30106, 301, 'Guadalupe o Arenilla'),
  (30107, 301, 'Corralillo'),
  (30108, 301, 'Tierra Blanca'),
  (30109, 301, 'Dulce Nombre'),
  (30110, 301, 'Llano Grande'),
  (30111, 301, 'Quebradilla'),
  (30201, 302, 'Paraíso'),
  (30202, 302, 'Santiago'),
  (30203, 302, 'Orosi'),
  (30204, 302, 'Cachí'),
  (30205, 302, 'Llanos de Santa Lucía'),
  (30206, 302, 'Birrisito'),
  (30301, 303, 'Tres Ríos'),
  (30302, 303, 'San Diego'),
  (30303, 303, 'San Juan'),
  (30304, 303, 'San Rafael'),
  (30305, 303, 'Concepción'),
  (30306, 303, 'Dulce Nombre'),
  (30307, 303, 'San Ramón'),
  (30308, 303, 'Río Azul'),
  (30401, 304, 'Juan Viñas'),
  (30402, 304, 'Tucurrique'),
  (30403, 304, 'Pejibaye'),
  (30404, 304, 'La Victoria'),
  (30501, 305, 'Turrialba'),
  (30502, 305, 'La Suiza'),
  (30503, 305, 'Peralta'),
  (30504, 305, 'Santa Cruz'),
  (30505, 305, 'Santa Teresita'),
  (30506, 305, 'Pavones'),
  (30507, 305, 'Tuis'),
  (30508, 305, 'Tayutic'),
  (30509, 305, 'Santa Rosa'),
  (30510, 305, 'Tres Equis'),
  (30511, 305, 'La Isabel'),
  (30512, 305, 'Chirripó'),
  (30601, 306, 'Pacayas'),
  (30602, 306, 'Cervantes'),
  (30603, 306, 'Capellades'),
  (30701, 307, 'San Rafael'),
  (30702, 307, 'Cot'),
  (30703, 307, 'Potrero Cerrado'),
  (30704, 307, 'Cipreses'),
  (30705, 307, 'Santa Rosa'),
  (30801, 308, 'El Tejar'),
  (30802, 308, 'San Isidro'),
  (30803, 308, 'Tobosi'),
  (30804, 308, 'Patio de Agua'),
  (40101, 401, 'Heredia'),
  (40102, 401, 'Mercedes'),
  (40103, 401, 'San Francisco'),
  (40104, 401, 'Ulloa'),
  (40105, 401, 'Varablanca'),
  (40201, 402, 'Barva'),
  (40202, 402, 'San Pedro'),
  (40203, 402, 'San Pablo'),
  (40204, 402, 'San Roque'),
  (40205, 402, 'Santa Lucía'),
  (40206, 402, 'San José de la Montaña'),
  (40207, 402, 'Puente Salas'),
  (40301, 403, 'Santo Domingo'),
  (40302, 403, 'San Vicente'),
  (40303, 403, 'San Miguel'),
  (40304, 403, 'Paracito'),
  (40305, 403, 'Santo Tomás'),
  (40306, 403, 'Santa Rosa'),
  (40307, 403, 'Tures'),
  (40308, 403, 'Pará'),
  (40401, 404, 'Santa Bárbara'),
  (40402, 404, 'San Pedro'),
  (40403, 404, 'San Juan'),
  (40404, 404, 'Jesús'),
  (40405, 404, 'Santo Domingo'),
  (40406, 404, 'Purabá'),
  (40501, 405, 'San Rafael'),
  (40502, 405, 'San Josecito'),
  (40503, 405, 'Santiago'),
  (40504, 405, 'Ángeles'),
  (40505, 405, 'Concepción'),
  (40601, 406, 'San Isidro'),
  (40602, 406, 'San José'),
  (40603, 406, 'Concepción'),
  (40604, 406, 'San Francisco'),
  (40701, 407, 'San Antonio'),
  (40702, 407, 'La Ribera'),
  (40703, 407, 'La Asunción'),
  (40801, 408, 'San Joaquín'),
  (40802, 408, 'Barrantes'),
  (40803, 408, 'Llorente'),
  (40901, 409, 'San Pablo'),
  (40902, 409, 'Rincón de Sabanilla'),
  (41001, 410, 'Puerto Viejo'),
  (41002, 410, 'La Virgen'),
  (41003, 410, 'Las Horquetas'),
  (41004, 410, 'Llanuras del Gaspar'),
  (41005, 410, 'Cureña'),
  (50101, 501, 'Liberia'),
  (50102, 501, 'Cañas Dulces'),
  (50103, 501, 'Mayorga'),
  (50104, 501, 'Nacascolo'),
  (50105, 501, 'Curubandé'),
  (50201, 502, 'Nicoya'),
  (50202, 502, 'Mansión'),
  (50203, 502, 'San Antonio'),
  (50204, 502, 'Quebrada Honda'),
  (50205, 502, 'Sámara'),
  (50206, 502, 'Nosara'),
  (50207, 502, 'Belén de Nosarita'),
  (50301, 503, 'Santa Cruz'),
  (50302, 503, 'Bolsón'),
  (50303, 503, 'Veintisiete de Abril'),
  (50304, 503, 'Tempate'),
  (50305, 503, 'Cartagena'),
  (50306, 503, 'Cuajiniquil'),
  (50307, 503, 'Diriá'),
  (50308, 503, 'Cabo Velas'),
  (50309, 503, 'Tamarindo'),
  (50401, 504, 'Bagaces'),
  (50402, 504, 'La Fortuna'),
  (50403, 504, 'Mogote'),
  (50404, 504, 'Río Naranjo'),
  (50405, 504, 'Pijije'),
  (50501, 505, 'Filadelfia'),
  (50502, 505, 'Palmira'),
  (50503, 505, 'Sardinal'),
  (50504, 505, 'Belén'),
  (50601, 506, 'Cañas'),
  (50602, 506, 'Palmira'),
  (50603, 506, 'San Miguel'),
  (50604, 506, 'Bebedero'),
  (50605, 506, 'Porozal'),
  (50701, 507, 'Las Juntas'),
  (50702, 507, 'Sierra'),
  (50703, 507, 'San Juan'),
  (50704, 507, 'Colorado'),
  (50801, 508, 'Tilarán'),
  (50802, 508, 'Quebrada Grande'),
  (50803, 508, 'Tronadora'),
  (50804, 508, 'Santa Rosa'),
  (50805, 508, 'Líbano'),
  (50806, 508, 'Tierras Morenas'),
  (50807, 508, 'Arenal'),
  (50808, 508, 'Cabeceras'),
  (50901, 509, 'Carmona'),
  (50902, 509, 'Santa Rita'),
  (50903, 509, 'Zapotal'),
  (50904, 509, 'San Pablo'),
  (50905, 509, 'Porvenir'),
  (50906, 509, 'Bejuco'),
  (51001, 510, 'La Cruz'),
  (51002, 510, 'Santa Cecilia'),
  (51003, 510, 'La Garita'),
  (51004, 510, 'Santa Elena'),
  (51101, 511, 'Hojancha'),
  (51102, 511, 'Monte Romo'),
  (51103, 511, 'Puerto Carrillo'),
  (51104, 511, 'Huacas'),
  (51105, 511, 'Matambú'),
  (60101, 601, 'Puntarenas'),
  (60102, 601, 'Pitahaya'),
  (60103, 601, 'Chomes'),
  (60104, 601, 'Lepanto'),
  (60105, 601, 'Paquera'),
  (60106, 601, 'Manzanillo'),
  (60107, 601, 'Guacimal'),
  (60108, 601, 'Barranca'),
  (60110, 601, 'Isla del Coco'),
  (60111, 601, 'Cóbano'),
  (60112, 601, 'Chacarita'),
  (60113, 601, 'Chira'),
  (60114, 601, 'Acapulco'),
  (60115, 601, 'El Roble'),
  (60116, 601, 'Arancibia'),
  (60201, 602, 'Espíritu Santo'),
  (60202, 602, 'San Juan Grande'),
  (60203, 602, 'Macacona'),
  (60204, 602, 'San Rafael'),
  (60205, 602, 'San Jerónimo'),
  (60206, 602, 'Caldera'),
  (60301, 603, 'Buenos Aires'),
  (60302, 603, 'Volcán'),
  (60303, 603, 'Potrero Grande'),
  (60304, 603, 'Boruca'),
  (60305, 603, 'Pilas'),
  (60306, 603, 'Colinas'),
  (60307, 603, 'Chánguena'),
  (60308, 603, 'Biolley'),
  (60309, 603, 'Brunka'),
  (60310, 603, 'Cabagra'),
  (60401, 604, 'Miramar'),
  (60402, 604, 'La Unión'),
  (60403, 604, 'San Isidro'),
  (60501, 605, 'Puerto Cortés'),
  (60502, 605, 'Palmar'),
  (60503, 605, 'Sierpe'),
  (60504, 605, 'Bahía Ballena'),
  (60505, 605, 'Piedras Blancas'),
  (60506, 605, 'Bahía Drake'),
  (60601, 606, 'Quepos'),
  (60602, 606, 'Savegre'),
  (60603, 606, 'Naranjito'),
  (60701, 607, 'Golfito'),
  (60703, 607, 'Guaycará'),
  (60704, 607, 'Pavón'),
  (60801, 608, 'San Vito'),
  (60802, 608, 'Sabalito'),
  (60803, 608, 'Aguabuena'),
  (60804, 608, 'Limoncito'),
  (60805, 608, 'Pittier'),
  (60806, 608, 'Gutiérrez Braun'),
  (60901, 609, 'Parrita'),
  (61001, 610, 'Corredor'),
  (61002, 610, 'La Cuesta'),
  (61003, 610, 'Canoas'),
  (61004, 610, 'Laurel'),
  (61101, 611, 'Jacó'),
  (61102, 611, 'Tárcoles'),
  (61103, 611, 'Lagunillas'),
  (61201, 612, 'Monteverde'),
  (61301, 613, 'Puerto Jiménez'),
  (70101, 701, 'Limón'),
  (70102, 701, 'Valle La Estrella'),
  (70103, 701, 'Río Blanco'),
  (70104, 701, 'Matama'),
  (70201, 702, 'Guápiles'),
  (70202, 702, 'Jiménez'),
  (70203, 702, 'Rita'),
  (70204, 702, 'Roxana'),
  (70205, 702, 'Cariari'),
  (70206, 702, 'Colorado'),
  (70207, 702, 'La Colonia'),
  (70301, 703, 'Siquirres'),
  (70302, 703, 'Pacuarito'),
  (70303, 703, 'Florida'),
  (70304, 703, 'Germania'),
  (70305, 703, 'El Cairo'),
  (70306, 703, 'Alegría'),
  (70307, 703, 'Reventazón'),
  (70401, 704, 'Bratsi'),
  (70402, 704, 'Sixaola'),
  (70403, 704, 'Cahuita'),
  (70404, 704, 'Telire'),
  (70501, 705, 'Matina'),
  (70502, 705, 'Batán'),
  (70503, 705, 'Carrandí'),
  (70601, 706, 'Guácimo'),
  (70602, 706, 'Mercedes'),
  (70603, 706, 'Pocora'),
  (70604, 706, 'Río Jiménez');


-- SEMANA 3

USE `dbrrsscita`;

CREATE TABLE IF NOT EXISTS `tbgenero` (
  `tbgeneroid` int NOT NULL AUTO_INCREMENT,
  `tbgeneronombre` varchar(100) NOT NULL,
  `tbgeneroestado` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbgeneroid`)
);

CREATE TABLE IF NOT EXISTS `tbcancion` (
  `tbcancionid` int NOT NULL AUTO_INCREMENT,
  `tbgeneroid` int NOT NULL,
  `tbcancionnombre` varchar(200) NOT NULL,
  `tbcancionartista` varchar(200) NOT NULL,
  `tbcancionurl` text NOT NULL,
  `tbcancionactivo` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbcancionid`)
);

CREATE TABLE IF NOT EXISTS `tbreproduccionsemanal` (
  `tbreproduccionsemanalid` int NOT NULL AUTO_INCREMENT,
  `tbreproduccionsemanaldata` TEXT NOT NULL,
  PRIMARY KEY (`tbreproduccionsemanalid`)
);

CREATE TABLE IF NOT EXISTS `tbreproduccion` (
  `tbreproduccionid` int NOT NULL AUTO_INCREMENT,
  `tbperfilid` int NOT NULL,
  `tbcancionid` int NOT NULL,
  `tbreproduccionsemanalid` int NOT NULL,
  `tbreproducciontiempo` int NOT NULL DEFAULT 0,
  `tbreproduccionestado` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbreproduccionid`),
  UNIQUE KEY `uq_perfil_cancion` (`tbperfilid`, `tbcancionid`)
);

INSERT INTO `tbgenero` (`tbgeneronombre`) VALUES
('Pop'),
('Rock'),
('Reggaetón'),
('Salsa'),
('Electrónica'),
('Jazz'),
('Hip Hop'),
('Rap'),
('Trap'),
('R&B'),
('Reggae'),
('Country'),
('Metal'),
('Bachata'),
('Merengue'),
('Cumbia'),
('Clásica'),
('Funk'),
('Punk'),
('Indie');

-- Canciones de ejemplo
INSERT INTO `tbcancion`
(`tbgeneroid`, `tbcancionnombre`, `tbcancionartista`, `tbcancionurl`) VALUES

-- 1. Pop
(1, 'Thriller', 'Michael Jackson', 'https://www.youtube.com/watch?v=sOnqjkJTMaA'),

-- 2. Rock
(2, 'Bohemian Rhapsody', 'Queen', 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ'),

-- 3. Reggaetón
(3, 'dIvA', 'Daze', 'https://youtu.be/bl_CXIsY-hg?si=G2fmUyqhG0ZvdQrN'),

-- 4. Salsa
(4, 'Vivir Mi Vida', 'Marc Anthony', 'https://www.youtube.com/watch?v=YXnjy5YlDwk'),

-- 5. Electrónica
(5, 'Titanium', 'David Guetta ft. Sia', 'https://www.youtube.com/watch?v=JRfuAukYTKg'),

-- 6. Jazz
(6, 'Feeling Good', 'Nina Simone', 'https://youtu.be/oHRNrgDIJfo'),

-- 7. Hip Hop
(7, 'God''s Plan', 'Drake', 'https://www.youtube.com/watch?v=xpVfcZ0ZcFM'),

-- 8. Rap
(8, 'Lose Yourself', 'Eminem', 'https://www.youtube.com/watch?v=_Yhyp-_hX2s'),

-- 9. Trap
(9, 'SICKO MODE', 'Travis Scott', 'https://www.youtube.com/watch?v=6ONRf7h3Mdk'),

-- 10. R&B
(10, 'Blinding Lights', 'The Weeknd', 'https://www.youtube.com/watch?v=4NRXx6U8ABQ'),

-- 11. Reggae
(11, 'Three Little Birds', 'Bob Marley & The Wailers', 'https://youtu.be/HNBCVM4KbUM'),

-- 12. Country
(12, 'Take Me Home, Country Roads', 'John Denver', 'https://www.youtube.com/watch?v=1vrEljMfXYo'),

-- 13. Metal
(13, 'Enter Sandman', 'Metallica', 'https://www.youtube.com/watch?v=CD-E-LDc384'),

-- 14. Bachata
(14, 'Propuesta Indecente', 'Romeo Santos', 'https://www.youtube.com/watch?v=QFs3PIZb3js'),

-- 15. Merengue
(15, 'Suavemente', 'Elvis Crespo', 'https://www.youtube.com/watch?v=YVw7eJ0vGfM'),

-- 16. Cumbia
(16, 'La Pollera Colorá', 'La Sonora Dinamita', 'https://www.youtube.com/watch?v=4JrIuFjUgNw&list=RD4JrIuFjUgNw&start_radio=1'),

-- 17. Clásica
(17, 'Für Elise', 'Ludwig van Beethoven', 'https://www.youtube.com/watch?v=3cvmONlV5WU&list=RD3cvmONlV5WU&start_radio=1'),

-- 18. Funk
(18, 'Get Lucky', 'Daft Punk ft. Pharrell Williams', 'https://www.youtube.com/watch?v=5NV6Rdv1a3I'),

-- 19. Punk
(19, 'American Idiot', 'Green Day', 'https://www.youtube.com/watch?v=Ee_uujKuJMI'),

-- 20. Indie
(20, 'Do I Wanna Know?', 'Arctic Monkeys', 'https://www.youtube.com/watch?v=bpOSxM0rNPM'),

-- ===== CANCIONES ADICIONALES POR GÉNERO (5 × 20 = 100) =====

-- 1. Pop
(1, 'Blinding Lights', 'The Weeknd', 'https://www.youtube.com/watch?v=4NRXx6U8ABQ'),
(1, 'Shape of You', 'Ed Sheeran', 'https://www.youtube.com/watch?v=JGwWNGJdvx8'),
(1, 'Bad Guy', 'Billie Eilish', 'https://www.youtube.com/watch?v=DyDfgMOUjCI'),
(1, 'Watermelon Sugar', 'Harry Styles', 'https://www.youtube.com/watch?v=E07s5ZYygMg'),
(1, 'Flowers', 'Miley Cyrus', 'https://www.youtube.com/watch?v=G7KNmW9a75Y'),

-- 2. Rock
(2, 'Bohemian Rhapsody', 'Queen', 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ'),
(2, 'Back in Black', 'AC/DC', 'https://www.youtube.com/watch?v=pAgnJDJN4VA'),
(2, 'Smells Like Teen Spirit', 'Nirvana', 'https://www.youtube.com/watch?v=hTWKbfoikeg'),
(2, 'Sweet Child O'' Mine', 'Guns N'' Roses', 'https://www.youtube.com/watch?v=1w7OgIMMRc4'),
(2, 'Wonderwall', 'Oasis', 'https://www.youtube.com/watch?v=bx1Bh8ZvH84'),

-- 3. Reggaetón
(3, 'Gasolina', 'Daddy Yankee', 'https://www.youtube.com/watch?v=CCF1_jI8Prk'),
(3, 'Dákiti', 'Bad Bunny & Jhay Cortez', 'https://www.youtube.com/watch?v=TmKh7lAwnBI'),
(3, 'Con Calma', 'Daddy Yankee & Snow', 'https://www.youtube.com/watch?v=DiItGE3eAyQ'),
(3, 'Provenza', 'Karol G', 'https://www.youtube.com/watch?v=ca48oMV59LU'),
(3, 'Tití Me Preguntó', 'Bad Bunny', 'https://www.youtube.com/watch?v=Cr8K88UcO0s'),

-- 4. Salsa
(4, 'La Vida Es Un Carnaval', 'Celia Cruz', 'https://www.youtube.com/watch?v=7Ho86ggAVrY'),
(4, 'Quimbara', 'Celia Cruz', 'https://www.youtube.com/watch?v=nlGzLj8ZVfw'),
(4, 'El Gran Varón', 'Willie Colón', 'https://www.youtube.com/watch?v=Qy0GworETbU'),
(4, 'Llorarás', 'Oscar D''León', 'https://www.youtube.com/watch?v=gxlB1B9emDc'),
(4, 'Cumbia Sampuesana', 'Aniceto Molina', 'https://www.youtube.com/watch?v=z-moPjEOsV4'),

-- 5. Electrónica
(5, 'Wake Me Up', 'Avicii', 'https://www.youtube.com/watch?v=IcrbM1l_BoI'),
(5, 'Faded', 'Alan Walker', 'https://www.youtube.com/watch?v=60ItHLz5WEA'),
(5, 'Lean On', 'Major Lazer & DJ Snake', 'https://www.youtube.com/watch?v=YqeW9_5kURI'),
(5, 'Animals', 'Martin Garrix', 'https://www.youtube.com/watch?v=gCYcHz2k5x0'),
(5, 'Clarity', 'Zedd ft. Foxes', 'https://www.youtube.com/watch?v=IxxstCcJlsc'),

-- 6. Jazz
(6, 'Take Five', 'Dave Brubeck', 'https://www.youtube.com/watch?v=i-r9-DC93J0'),
(6, 'So What', 'Miles Davis', 'https://www.youtube.com/watch?v=zqNTltOGh5c'),
(6, 'Take the A Train', 'Duke Ellington', 'https://www.youtube.com/watch?v=D6mFGy4g_n8'),
(6, 'Fly Me to the Moon', 'Frank Sinatra', 'https://www.youtube.com/watch?v=Y2rDb4Ur2dw'),
(6, 'My Funny Valentine', 'Chet Baker', 'https://www.youtube.com/watch?v=UOEIQKczRPY'),

-- 7. Hip Hop
(7, 'HUMBLE.', 'Kendrick Lamar', 'https://www.youtube.com/watch?v=tvTRZJ-4EyI'),
(7, 'In Da Club', '50 Cent', 'https://www.youtube.com/watch?v=5qm8PH4xAss'),
(7, 'Juicy', 'The Notorious B.I.G.', 'https://www.youtube.com/watch?v=_JZom_gVfuw'),
(7, 'Alright', 'Kendrick Lamar', 'https://www.youtube.com/watch?v=Z-48u_uWMHY'),
(7, 'Nuthin'' But a G Thang', 'Dr. Dre & Snoop Dogg', 'https://www.youtube.com/watch?v=8GliyDgAGQI'),

-- 8. Rap
(8, 'Mockingbird', 'Eminem', 'https://www.youtube.com/watch?v=S9bCLPwzSC0'),
(8, 'Rap God', 'Eminem', 'https://www.youtube.com/watch?v=XbGs_qK2PQA'),
(8, 'Not Afraid', 'Eminem', 'https://www.youtube.com/watch?v=j5-yKhDd64s'),
(8, 'Sing for the Moment', 'Eminem', 'https://www.youtube.com/watch?v=D4hAVemuQXY'),
(8, 'Stan', 'Eminem ft. Dido', 'https://www.youtube.com/watch?v=gOMhN-hfMtY'),

-- 9. Trap
(9, 'XO Tour Llif3', 'Lil Uzi Vert', 'https://www.youtube.com/watch?v=WrsFXgQk5UI'),
(9, 'Rockstar', 'Post Malone ft. 21 Savage', 'https://www.youtube.com/watch?v=UceaB4D0jpo'),
(9, 'Me Gusta', 'Anitta ft. Cardi B & Myke Towers', 'https://www.youtube.com/watch?v=kIbjHtE4fd8'),
(9, 'Lucid Dreams', 'Juice WRLD', 'https://www.youtube.com/watch?v=mzB1VGEGcSU'),
(9, 'Whoopty', 'CJ', 'https://www.youtube.com/watch?v=2xWkATdMQms'),

-- 10. R&B
(10, 'Earned It', 'The Weeknd', 'https://www.youtube.com/watch?v=waU75jdUnYw'),
(10, 'No Guidance', 'Chris Brown ft. Drake', 'https://www.youtube.com/watch?v=6L_k74BOLag'),
(10, 'Cranes in the Sky', 'Solange', 'https://www.youtube.com/watch?v=S0qrinhNnOM'),
(10, 'Adorn', 'Miguel', 'https://www.youtube.com/watch?v=8dM5QYdTo08'),
(10, 'Best Part', 'Daniel Caesar ft. H.E.R.', 'https://www.youtube.com/watch?v=hKgl5-lkT8U'),

-- 11. Reggae
(11, 'No Woman, No Cry', 'Bob Marley', 'https://www.youtube.com/watch?v=IT8XvzIfi4U'),
(11, 'Redemption Song', 'Bob Marley', 'https://www.youtube.com/watch?v=yv5xonFSC4c'),
(11, 'Could You Be Loved', 'Bob Marley', 'https://www.youtube.com/watch?v=1ti2YCFgCoI'),
(11, '54-46 Was My Number', 'Toots & The Maytals', 'https://www.youtube.com/watch?v=UhH1Lxv-8sA'),
(11, 'The Israelites', 'Desmond Dekker', 'https://www.youtube.com/watch?v=HA1ZRIQuHy4'),

-- 12. Country
(12, 'Jolene', 'Dolly Parton', 'https://www.youtube.com/watch?v=5m71Jbi7NkU'),
(12, 'Ring of Fire', 'Johnny Cash', 'https://www.youtube.com/watch?v=5WyLhwYFgmk'),
(12, 'Friends in Low Places', 'Garth Brooks', 'https://www.youtube.com/watch?v=0e_HtjZS8SQ'),
(12, 'Before He Cheats', 'Carrie Underwood', 'https://www.youtube.com/watch?v=WaSy8yy-mr8'),
(12, 'Cruise', 'Florida Georgia Line', 'https://www.youtube.com/watch?v=8PvebsWcpto'),

-- 13. Metal
(13, 'Master of Puppets', 'Metallica', 'https://www.youtube.com/watch?v=E0ozmU9cJDg'),
(13, 'Paranoid', 'Black Sabbath', 'https://www.youtube.com/watch?v=0qanF-91aJo'),
(13, 'Ace of Spades', 'Motörhead', 'https://www.youtube.com/watch?v=3mbvWn1EY6g'),
(13, 'Raining Blood', 'Slayer', 'https://www.youtube.com/watch?v=d3-ITn0e00U'),
(13, 'The Trooper', 'Iron Maiden', 'https://www.youtube.com/watch?v=X4bgXH3sJ2Q'),

-- 14. Bachata
(14, 'Obsesión', 'Aventura', 'https://www.youtube.com/watch?v=8_QY5gFQUTg'),
(14, 'Bachata Rosa', 'Juan Luis Guerra', 'https://www.youtube.com/watch?v=_9Gct2IK02Y'),
(14, 'Darte un Beso', 'Prince Royce', 'https://www.youtube.com/watch?v=bdOXnTbyk0g'),
(14, 'La Carretera', 'Prince Royce', 'https://www.youtube.com/watch?v=OdaIbTUGmHM'),
(14, 'Corazón Sin Cara', 'Prince Royce', 'https://www.youtube.com/watch?v=XNGWDH-6yv8'),

-- 15. Merengue
(15, 'La Bilirrubina', 'Juan Luis Guerra', 'https://www.youtube.com/watch?v=x3G4VFThbEg'),
(15, 'Ojalá Que Llueva Café', 'Juan Luis Guerra', 'https://www.youtube.com/watch?v=suQC8d-YkeU'),
(15, 'Burbujas de Amor', 'Juan Luis Guerra', 'https://www.youtube.com/watch?v=eaBk4-UT53U'),
(15, 'Rosalía', 'Juan Luis Guerra', 'https://www.youtube.com/watch?v=81p_KumYYt8'),
(15, 'La Dueña del Swing', 'Los Hermanos Rosario', 'https://www.youtube.com/watch?v=QCcBiAwp9nc'),

-- 16. Cumbia
(16, 'Nunca Es Suficiente', 'Los Ángeles Azules ft. Natalia Lafourcade', 'https://www.youtube.com/watch?v=k76BgIb89-s'),
(16, 'Cumbia Sobre el Rio', 'Celso Piña', 'https://www.youtube.com/watch?v=4ba1Yc6lkQc'),
(16, 'Mis Sentimientos', 'Los Ángeles Azules', 'https://www.youtube.com/watch?v=BokdSWC2R68'),
(16, 'Como Te Voy a Olvidar', 'Los Ángeles Azules', 'https://www.youtube.com/watch?v=nxXvOEPsE0s'),
(16, 'Locura de Amor', 'Agua Marina', 'https://www.youtube.com/watch?v=UiEKd6C4wbQ'),

-- 17. Clásica
(17, 'Clair de Lune', 'Claude Debussy', 'https://www.youtube.com/watch?v=WNcsUNKlAKw'),
(17, 'Canon en D', 'Johann Pachelbel', 'https://www.youtube.com/watch?v=NlprozGcs80'),
(17, 'Las Cuatro Estaciones', 'Antonio Vivaldi', 'https://www.youtube.com/watch?v=4rgSzQwe5DQ'),
(17, 'Concierto de Aranjuez', 'Joaquín Rodrigo', 'https://www.youtube.com/watch?v=Idsb6gk6j_U'),
(17, 'Sinfonía No. 5', 'Ludwig van Beethoven', 'https://www.youtube.com/watch?v=a9UApyClFKA'),

-- 18. Funk
(18, 'Superstition', 'Stevie Wonder', 'https://www.youtube.com/watch?v=ftdZ363R9kQ'),
(18, 'September', 'Earth, Wind & Fire', 'https://www.youtube.com/watch?v=Gs069dndIYk'),
(18, 'Give Up the Funk', 'Parliament', 'https://www.youtube.com/watch?v=xNpNTkSkrU8'),
(18, 'Brick House', 'Commodores', 'https://www.youtube.com/watch?v=ZdJmXeod0RE'),
(18, 'Play That Funky Music', 'Wild Cherry', 'https://www.youtube.com/watch?v=BHcYFxU4fMo'),

-- 19. Punk
(19, 'Basket Case', 'Green Day', 'https://www.youtube.com/watch?v=NUTGr5t3MoY'),
(19, 'Blitzkrieg Bop', 'Ramones', 'https://www.youtube.com/watch?v=268C3N2dDYk'),
(19, 'Smash It Up', 'The Damned', 'https://www.youtube.com/watch?v=-FFx68qSAuY'),
(19, 'London Calling', 'The Clash', 'https://www.youtube.com/watch?v=EfK-WX2pa8c'),
(19, 'Everlong', 'Foo Fighters', 'https://www.youtube.com/watch?v=eBG7P-K-r1Y'),

-- 20. Indie
(20, 'The Less I Know the Better', 'Tame Impala', 'https://www.youtube.com/watch?v=sBzrzS1Ag_g'),
(20, 'Somebody Else', 'The 1975', 'https://www.youtube.com/watch?v=Bimd2nZirT4'),
(20, 'Sweater Weather', 'The Neighbourhood', 'https://www.youtube.com/watch?v=GCdwKhTtNNw'),
(20, 'Heat Waves', 'Glass Animals', 'https://www.youtube.com/watch?v=mRD0-GxqHVo'),
(20, 'Electric Feel', 'MGMT', 'https://www.youtube.com/watch?v=MmZexg8sxyk');

-- Semana 4

USE `dbrrsscita`;

CREATE TABLE IF NOT EXISTS `tbperfilaccesosemanal` (
  `tbperfilaccesosemanalid` int NOT NULL AUTO_INCREMENT,
  `tbperfilaccesosemanaldata` TEXT NOT NULL,
  PRIMARY KEY (`tbperfilaccesosemanalid`)
);

INSERT INTO `tbperfilaccesosemanal` (`tbperfilaccesosemanalid`, `tbperfilaccesosemanaldata`)
VALUES
  (1, ''),
  (2, '');

CREATE TABLE IF NOT EXISTS `tbperfilacceso` (
  `tbperfilaccesoid` int NOT NULL AUTO_INCREMENT,
  `tbperfilid` int NOT NULL,
  `tbperfilaccesosemanalid` int NOT NULL,
  `tbperfilaccesofechacreacion` datetime NOT NULL,
  `tbperfilaccesofechaultima` datetime NOT NULL,
  `tbperfilaccesoestado` BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbperfilaccesoid`),
  UNIQUE KEY `uq_perfilacceso_perfil` (`tbperfilid`)
);

INSERT INTO `tbperfilacceso`
  (`tbperfilid`, `tbperfilaccesosemanalid`, `tbperfilaccesofechacreacion`, `tbperfilaccesofechaultima`)
VALUES
  (1, 1, '2026-01-15 08:00:00', '2026-01-15 08:00:00'),
  (2, 2, '2026-01-15 08:00:00', '2026-01-15 08:00:00');

-- Semana 5

USE `dbrrsscita`;

CREATE TABLE IF NOT EXISTS `tbperfilubicacion` (
  `tbperfilubicacionid` int NOT NULL AUTO_INCREMENT,
  `tbperfilid` int NOT NULL,
  `tbperfilubicaciondata` TEXT NOT NULL,
  `tbperfilubicacionestado` BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbperfilubicacionid`)
);

CREATE TABLE IF NOT EXISTS `tbperfilregistrossemanal` (
  `tbperfilregistrossemanalid` int NOT NULL AUTO_INCREMENT,
  `tbperfilid` int NOT NULL,
  `tbperfilregistroscontradata` TEXT NOT NULL,
  `tbperfilregistroscorreodata` TEXT NOT NULL,
  `tbperfilregistrosnombredata` TEXT NOT NULL,
  PRIMARY KEY (`tbperfilregistrossemanalid`),
  UNIQUE KEY `uq_perfilregistrossemanal_perfil` (`tbperfilid`)
);


-- semana 7
-- Módulo Eventos

USE `dbrrsscita`;

CREATE TABLE IF NOT EXISTS `tbevento` (
  `tbeventoid` int NOT NULL AUTO_INCREMENT,
  `tbgeneroid` int NOT NULL,
  `tbeventonombre` varchar(150) NOT NULL,
  `tbeventoartista` varchar(150) NOT NULL,
  `tbeventoubicaciongeneral` varchar(200) NOT NULL,
  `tbeventolatitud` decimal(10,8) NOT NULL,
  `tbeventolongitud` decimal(10,8) NOT NULL,
  `tbeventofecha` date NOT NULL,
  `tbeventohora` time NOT NULL,
  `tbeventoestado` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbeventoid`)
);

INSERT INTO tbevento
(tbgeneroid, tbeventonombre, tbeventoartista, tbeventoubicaciongeneral,
tbeventolatitud, tbeventolongitud, tbeventofecha, tbeventohora)
VALUES
(3, 'Wisin en Costa Rica', 'Wisin', 'Estadio Nacional, San José',
9.93500000, -84.10670000, '2026-09-12', '19:00:00'),

(3, 'Arcángel - La 8va Maravilla', 'Arcángel', 'Parque Viva, Alajuela',
10.00040000, -84.25800000, '2026-09-26', '19:00:00'),

(2, 'Noche de Rock', 'Rock Fest CR', 'Estadio Nacional, San José',
9.93500000, -84.10670000, '2026-10-03', '18:00:00'),

(4, 'Festival de Salsa', 'Salsa Costa Rica', 'Centro de Eventos Pedregal, Heredia',
9.99810000, -84.16150000, '2026-10-10', '19:00:00'),

(5, 'Electronic Night', 'DJ Martin Garrix', 'Parque Viva, Alajuela',
10.00040000, -84.25800000, '2026-10-17', '20:00:00'),

(14, 'Noche de Bachata', 'Romeo Santos', 'Estadio Nacional, San José',
9.93500000, -84.10670000, '2026-10-24', '20:00:00'),

(1, 'Pop Night Costa Rica', 'Greeicy', 'Estadio Nacional, San José',
9.93500000, -84.10670000, '2026-10-31', '20:00:00'),

(7, 'Hip Hop Fest', 'Trueno', 'Centro de Eventos Pedregal, Heredia',
9.99810000, -84.16150000, '2026-11-07', '19:00:00');

CREATE TABLE IF NOT EXISTS `tbeventoasistencia` (
  `tbeventoasistenciaid` int NOT NULL AUTO_INCREMENT,
  `tbperfilid` int NOT NULL,
  `tbeventoid` int NOT NULL,
  `tbeventoasistenciafechahora` datetime NOT NULL,
  `tbeventoasistencialatitud` decimal(10,8) NOT NULL,
  `tbeventoasistencialongitud` decimal(10,8) NOT NULL,
  `tbeventoasistenciacoincide` boolean NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`tbeventoasistenciaid`)
);

CREATE TABLE IF NOT EXISTS `tbeventoubicacion` (
  `tbeventoubicacionid` int NOT NULL AUTO_INCREMENT,
  `tbeventoid` int NOT NULL,
  `tbeventoubicacioncategoria` varchar(50) NOT NULL,
  `tbeventoubicacionreferenciaid` int NULL,
  `tbeventoubicacionnombre` varchar(200) NOT NULL,
  `tbeventoubicacionlatitud` decimal(10,8) NULL,
  `tbeventoubicacionlongitud` decimal(10,8) NULL,
  `tbeventoubicacionestado` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbeventoubicacionid`)
);

INSERT INTO tbeventoubicacion
(tbeventoid, tbeventoubicacioncategoria, tbeventoubicacionreferenciaid, tbeventoubicacionnombre, tbeventoubicacionlatitud, tbeventoubicacionlongitud)
VALUES
(1, 'comida', 16, 'Soda Doña Rosa', 9.93510000, -84.10680000),
(1, 'comida', 10, 'Puesto de hamburguesas', 9.93520000, -84.10650000),
(2, 'comida', 1, 'Food truck de tacos', 10.00050000, -84.25790000),
(4, 'comida', 14, 'Café Pedregal', 9.99800000, -84.16140000),
(6, 'comida', 16, 'Casados El Estadio', 9.93490000, -84.10660000);

--FRANJAS HORARIAS PERSONALIZADAS

-- Guarda las franjas personalizadas calculadas para cada perfil (se sobreescribe cada vez que se genera el perfilado)
CREATE TABLE IF NOT EXISTS `tbperfilfranjas` (
  `tbperfilfranjasid` int NOT NULL AUTO_INCREMENT,
  `tbperfilid` int NOT NULL,
  `tbperfilfranjasmadrugadainicio` TINYINT NOT NULL DEFAULT 0,
  `tbperfilfranjasmananainicio` TINYINT NOT NULL DEFAULT 6,
  `tbperfilfranjastardeinicio` TINYINT NOT NULL DEFAULT 12,
  `tbperfilfranjasnocheinicio` TINYINT NOT NULL DEFAULT 18,
  `tbperfilfranjasfechacalculo` datetime NOT NULL,
  PRIMARY KEY (`tbperfilfranjasid`),
  UNIQUE KEY `uq_perfilfranjas_perfil` (`tbperfilid`)
);

-- Caché de feriados de Costa Rica (se refresca una vez por año desde el web service)
CREATE TABLE IF NOT EXISTS `tbferiados` (
  `tbferiadosid` int NOT NULL AUTO_INCREMENT,
  `tbferiadosfecha` date NOT NULL,
  `tbferiadosnombre` varchar(150) NOT NULL,
  `tbferiadosanio` int NOT NULL,
  PRIMARY KEY (`tbferiadosid`),
  UNIQUE KEY `uq_feriados_fecha` (`tbferiadosfecha`)
);


-- Semana 8

USE `dbrrsscita`;

-- MINERÍA IMPLÍCITA — COMIDA (perfilado por swipe, sin encuesta)

CREATE TABLE IF NOT EXISTS `tbtipocomida` (
  `tbtipocomidaid` int NOT NULL AUTO_INCREMENT,
  `tbtipocomidanombre` varchar(100) NOT NULL,
  `tbtipocomidaestado` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbtipocomidaid`)
);

CREATE TABLE IF NOT EXISTS `tbcomida` (
  `tbcomidaid` int NOT NULL AUTO_INCREMENT,
  `tbtipocomidaid` int NOT NULL,
  `tbcomidanombre` varchar(200) NOT NULL,
  `tbcomidaimagenurl` text,
  `tbcomidaactivo` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbcomidaid`),
  FOREIGN KEY (`tbtipocomidaid`) REFERENCES `tbtipocomida`(`tbtipocomidaid`)
);

CREATE TABLE IF NOT EXISTS `tbcomidaswipe` (
  `tbcomidaswipeid` int NOT NULL AUTO_INCREMENT,
  `tbperfilid` int NOT NULL,
  `tbcomidaid` int NOT NULL,
  `tbcomidaswipelike` boolean NOT NULL,
  `tbcomidaswipefecha` datetime NOT NULL,
  PRIMARY KEY (`tbcomidaswipeid`),
  UNIQUE KEY `uq_perfil_comida` (`tbperfilid`, `tbcomidaid`)
);

INSERT INTO `tbtipocomida` (`tbtipocomidanombre`) VALUES
('Mexicana'),
('Italiana'),
('Japonesa'),
('China'),
('Tailandesa'),
('India'),
('Mediterránea'),
('Vegana'),
('Postres'),
('Comida rápida'),
('Mariscos'),
('Parrilla'),
('Pizza'),
('Café y brunch'),
('Saludable'),
('Costarricense');

INSERT INTO `tbcomida` (`tbtipocomidaid`, `tbcomidanombre`, `tbcomidaimagenurl`) VALUES
-- 1. Mexicana
(1, 'Tacos al pastor', 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?auto=format&fit=crop&w=600&q=60'),
(1, 'Burrito de frijoles', 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?auto=format&fit=crop&w=600&q=60'),
(1, 'Guacamole con totopos', 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?auto=format&fit=crop&w=600&q=60'),
(1, 'Enchiladas verdes', 'https://images.unsplash.com/photo-1534352956036-cd81e27dd615?auto=format&fit=crop&w=600&q=60'),
(1, 'Quesadillas de queso', 'https://images.unsplash.com/photo-1618040996337-56904b7850b9?auto=format&fit=crop&w=600&q=60'),
-- 2. Italiana
(2, 'Spaghetti a la carbonara', 'https://images.unsplash.com/photo-1612874742237-6526221588e3?auto=format&fit=crop&w=600&q=60'),
(2, 'Lasagna clásica', 'https://images.unsplash.com/photo-1574894709920-11b28e7367e3?auto=format&fit=crop&w=600&q=60'),
(2, 'Pasta al pomodoro', 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=600&q=60'),
(2, 'Risotto de hongos', 'https://images.unsplash.com/photo-1476124369491-e7addf5db371?auto=format&fit=crop&w=600&q=60'),
(2, 'Gnocchi con salsa', 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=600&q=60'),
-- 3. Japonesa
(3, 'Sushi de salmón', 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?auto=format&fit=crop&w=600&q=60'),
(3, 'Ramen tonkotsu', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=600&q=60'),
(3, 'Sashimi fresco', 'https://images.unsplash.com/photo-1563612116625-3012372fccce?auto=format&fit=crop&w=600&q=60'),
(3, 'Nigiri de atún', 'https://images.unsplash.com/photo-1553621042-f6e147245754?auto=format&fit=crop&w=600&q=60'),
(3, 'Tabla de sushi mixto', 'https://images.unsplash.com/photo-1543087903-1ac2ec7aa8c5?auto=format&fit=crop&w=600&q=60'),
-- 4. China
(4, 'Dumplings al vapor', 'https://images.unsplash.com/photo-1496116218417-1a781b1c416c?auto=format&fit=crop&w=600&q=60'),
(4, 'Arroz frito', 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?auto=format&fit=crop&w=600&q=60'),
(4, 'Fideos chow mein', 'https://images.unsplash.com/photo-1585032226651-759b368d7246?auto=format&fit=crop&w=600&q=60'),
(4, 'Rollos primavera', 'https://images.unsplash.com/photo-1553163147-622ab57be1c7?auto=format&fit=crop&w=600&q=60'),
(4, 'Hot pot de verduras', 'https://images.unsplash.com/photo-1525755662778-989d0524087e?auto=format&fit=crop&w=600&q=60'),
-- 5. Tailandesa
(5, 'Pad thai', 'https://images.unsplash.com/photo-1559314809-0d155014e29e?auto=format&fit=crop&w=600&q=60'),
(5, 'Curry tailandés', 'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?auto=format&fit=crop&w=600&q=60'),
(5, 'Tom yum', 'https://images.unsplash.com/photo-1535320903710-d993d3d77d29?auto=format&fit=crop&w=600&q=60'),
(5, 'Ensalada de papaya', 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=600&q=60'),
(5, 'Arroz pegajoso con mango', 'https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?auto=format&fit=crop&w=600&q=60'),
-- 6. India
(6, 'Butter chicken', 'https://images.unsplash.com/photo-1603894584373-5ac82b2ae398?auto=format&fit=crop&w=600&q=60'),
(6, 'Biryani de cordero', 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?auto=format&fit=crop&w=600&q=60'),
(6, 'Curry de garbanzos', 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=600&q=60'),
(6, 'Naan con mantequilla', 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=600&q=60'),
(6, 'Samosa crujiente', 'https://images.unsplash.com/photo-1601050690117-94f5f6fa8bd7?auto=format&fit=crop&w=600&q=60'),
-- 7. Mediterránea
(7, 'Ensalada griega', 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=600&q=60'),
(7, 'Hummus con pan pita', 'https://images.unsplash.com/photo-1577805947697-89e18249d767?auto=format&fit=crop&w=600&q=60'),
(7, 'Falafel', 'https://images.unsplash.com/photo-1593001874117-c99c800e3eb7?auto=format&fit=crop&w=600&q=60'),
(7, 'Mezze variado', 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=600&q=60'),
(7, 'Tabulé con limón', 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=600&q=60'),
-- 8. Vegana
(8, 'Buddha bowl', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=600&q=60'),
(8, 'Hamburguesa vegana', 'https://images.unsplash.com/photo-1520072959219-c595dc870360?auto=format&fit=crop&w=600&q=60'),
(8, 'Ensalada de quinoa', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=600&q=60'),
(8, 'Smoothie bowl', 'https://images.unsplash.com/photo-1490474418585-ba9bad8fd0ea?auto=format&fit=crop&w=600&q=60'),
(8, 'Ensalada verde fresca', 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=60'),
-- 9. Postres
(9, 'Pastel de chocolate', 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=600&q=60'),
(9, 'Cheesecake de fresa', 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?auto=format&fit=crop&w=600&q=60'),
(9, 'Helado artesanal', 'https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?auto=format&fit=crop&w=600&q=60'),
(9, 'Galletas con chispas', 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=600&q=60'),
(9, 'Tarta de frutos rojos', 'https://images.unsplash.com/photo-1464305795204-6f5bbfc7fb81?auto=format&fit=crop&w=600&q=60'),
-- 10. Comida rápida
(10, 'Hamburguesa clásica', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=60'),
(10, 'Papas fritas con queso', 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=600&q=60'),
(10, 'Perro caliente', 'https://images.unsplash.com/photo-1576946841371-2b26dbb0d73a?auto=format&fit=crop&w=600&q=60'),
(10, 'Pollo frito crujiente', 'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58?auto=format&fit=crop&w=600&q=60'),
(10, 'Nuggets con salsas', 'https://images.unsplash.com/photo-1562967914-608f82629710?auto=format&fit=crop&w=600&q=60'),
-- 11. Mariscos
(11, 'Camarones al ajillo', 'https://images.unsplash.com/photo-1565680018434-b513d5e5fd47?auto=format&fit=crop&w=600&q=60'),
(11, 'Ceviche de pescado', 'https://images.unsplash.com/photo-1535399831218-d5bd36d1a6b3?auto=format&fit=crop&w=600&q=60'),
(11, 'Mejillones al vapor', 'https://images.unsplash.com/photo-1534080564583-6be75777b70a?auto=format&fit=crop&w=600&q=60'),
(11, 'Pescado a la plancha', 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=600&q=60'),
(11, 'Arroz marinero', 'https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?auto=format&fit=crop&w=600&q=60'),
-- 12. Parrilla
(12, 'Costillas BBQ', 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=600&q=60'),
(12, 'Churrasco jugoso', 'https://images.unsplash.com/photo-1600891964092-4316c288032e?auto=format&fit=crop&w=600&q=60'),
(12, 'Pollo a las brasas', 'https://images.unsplash.com/photo-1558030006-450675393462?auto=format&fit=crop&w=600&q=60'),
(12, 'Brochetas de carne', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=600&q=60'),
(12, 'Chorizo al carbón', 'https://images.unsplash.com/photo-1555658636-4f4c8943e94c?auto=format&fit=crop&w=600&q=60'),
-- 13. Pizza
(13, 'Pizza margherita', 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&w=600&q=60'),
(13, 'Pizza pepperoni', 'https://images.unsplash.com/photo-1628840042765-356cda07504e?auto=format&fit=crop&w=600&q=60'),
(13, 'Slice de pizza', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=600&q=60'),
(13, 'Pizza napolitana', 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=600&q=60'),
(13, 'Pizza de cuatro quesos', 'https://images.unsplash.com/photo-1604382355076-af4b0eb60143?auto=format&fit=crop&w=600&q=60'),
-- 14. Café y brunch
(14, 'Avocado toast', 'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?auto=format&fit=crop&w=600&q=60'),
(14, 'Pancakes con miel', 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?auto=format&fit=crop&w=600&q=60'),
(14, 'Café con leche', 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=600&q=60'),
(14, 'Omelette de la casa', 'https://images.unsplash.com/photo-1510693206972-df098062cb71?auto=format&fit=crop&w=600&q=60'),
(14, 'Huevos benedictinos', 'https://images.unsplash.com/photo-1608039829572-78524f79c4c7?auto=format&fit=crop&w=600&q=60'),
-- 15. Saludable
(15, 'Poke bowl de atún', 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=60'),
(15, 'Wrap de pollo', 'https://images.unsplash.com/photo-1485451456034-3f9391c6f80f?auto=format&fit=crop&w=600&q=60'),
(15, 'Bowl de frutas', 'https://images.unsplash.com/photo-1490474418585-ba9bad8fd0ea?auto=format&fit=crop&w=600&q=60'),
(15, 'Jugo verde', 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=600&q=60'),
(15, 'Ensalada de atún', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=600&q=60'),
-- 16. Costarricense
(16, 'Casado completo', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=60'),
(16, 'Gallo pinto con huevo', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=60'),
(16, 'Arroz con pollo', 'https://images.unsplash.com/photo-1596797038530-2c107229654b?auto=format&fit=crop&w=600&q=60'),
(16, 'Patacones con salsa', 'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?auto=format&fit=crop&w=600&q=60'),
(16, 'Ceviche tico de corvina', 'https://images.unsplash.com/photo-1535399831218-d5bd36d1a6b3?auto=format&fit=crop&w=600&q=60');


-- MINERÍA IMPLÍCITA — DEPORTE (perfilado por swipe, sin encuesta)

CREATE TABLE IF NOT EXISTS `tbtipodeporte` (
  `tbtipodeporteid` int NOT NULL AUTO_INCREMENT,
  `tbtipodeportenombre` varchar(100) NOT NULL,
  `tbtipodeporteestado` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbtipodeporteid`)
);

CREATE TABLE IF NOT EXISTS `tbdeporte` (
  `tbdeporteid` int NOT NULL AUTO_INCREMENT,
  `tbtipodeporteid` int NOT NULL,
  `tbdeportenombre` varchar(200) NOT NULL,
  `tbdeporteimagenurl` text,
  `tbdeporteactivo` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`tbdeporteid`),
  FOREIGN KEY (`tbtipodeporteid`) REFERENCES `tbtipodeporte`(`tbtipodeporteid`)
);

CREATE TABLE IF NOT EXISTS `tbdeporteswipe` (
  `tbdeporteswipeid` int NOT NULL AUTO_INCREMENT,
  `tbperfilid` int NOT NULL,
  `tbdeporteid` int NOT NULL,
  `tbdeporteswipelike` boolean NOT NULL,
  `tbdeporteswipefecha` datetime NOT NULL,
  PRIMARY KEY (`tbdeporteswipeid`),
  UNIQUE KEY `uq_perfil_deporte` (`tbperfilid`, `tbdeporteid`)
);

INSERT INTO `tbtipodeporte` (`tbtipodeportenombre`) VALUES
('Fútbol'),
('Baloncesto'),
('Yoga'),
('Ciclismo'),
('Running'),
('Artes marciales'),
('Natación'),
('Crossfit'),
('Senderismo'),
('Tenis'),
('Voleibol'),
('Entrenamiento de fuerza'),
('Baile'),
('Skateboarding'),
('Surf'),
('Escalada');

INSERT INTO `tbdeporte` (`tbtipodeporteid`, `tbdeportenombre`, `tbdeporteimagenurl`) VALUES
-- 1. Fútbol
(1, 'Fútbol callejero', 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=600&q=60'),
(1, 'Campeonato de copas', 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=600&q=60'),
(1, 'Media cancha', 'https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=600&q=60'),
(1, 'Atajadas de gol', 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=600&q=60'),
(1, 'Tarde de estadio', 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=600&q=60'),
-- 2. Baloncesto
(2, 'Dunk en la cancha', 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=600&q=60'),
(2, 'Tiro libre', 'https://images.unsplash.com/photo-1504450758481-7338eba7524a?auto=format&fit=crop&w=600&q=60'),
(2, 'Partido 5x5', 'https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=600&q=60'),
(2, 'Clavada', 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=600&q=60'),
(2, 'Triple desde la esquina', 'https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=600&q=60'),
-- 3. Yoga
(3, 'Vinyasa matutina', 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=600&q=60'),
(3, 'Sesión de meditación', 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=600&q=60'),
(3, 'Yin yoga relajante', 'https://images.unsplash.com/photo-1510894347713-5fc3b8815d94?auto=format&fit=crop&w=600&q=60'),
(3, 'Posturas de fuerza', 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=600&q=60'),
(3, 'Namaste al atardecer', 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=600&q=60'),
-- 4. Ciclismo
(4, 'Ruta de montaña', 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=600&q=60'),
(4, 'Rodada urbana', 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=600&q=60'),
(4, 'Carrera de ruta', 'https://images.unsplash.com/photo-1532298229144-0ec0c57515c7?auto=format&fit=crop&w=600&q=60'),
(4, 'MTB en senderos', 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=600&q=60'),
(4, 'Pedaleo matinal', 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=600&q=60'),
-- 5. Running
(5, 'Carrera de 5K', 'https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?auto=format&fit=crop&w=600&q=60'),
(5, 'Maratón completo', 'https://images.unsplash.com/photo-1520975916090-3105956dac38?auto=format&fit=crop&w=600&q=60'),
(5, 'Running al amanecer', 'https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?auto=format&fit=crop&w=600&q=60'),
(5, 'Entrenamiento de velocidad', 'https://images.unsplash.com/photo-1520975916090-3105956dac38?auto=format&fit=crop&w=600&q=60'),
(5, 'Trote de recuperación', 'https://images.unsplash.com/photo-1571008887538-b36bb32f4571?auto=format&fit=crop&w=600&q=60'),
-- 6. Artes marciales
(6, 'Karate', 'https://images.unsplash.com/photo-1583473848882-f9a5bc7fd2ee?auto=format&fit=crop&w=600&q=60'),
(6, 'Muay thai', 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=600&q=60'),
(6, 'Judo', 'https://images.unsplash.com/photo-1583473848882-f9a5bc7fd2ee?auto=format&fit=crop&w=600&q=60'),
(6, 'Taekwondo', 'https://images.unsplash.com/photo-1510877438820-026c862b0a8f?auto=format&fit=crop&w=600&q=60'),
(6, 'Boxeo', 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=600&q=60'),
-- 7. Natación
(7, 'Piscina libre', 'https://images.unsplash.com/photo-1439405326854-014607f694d7?auto=format&fit=crop&w=600&q=60'),
(7, 'Nado de mar abierto', 'https://images.unsplash.com/photo-1530549387789-4c1017266635?auto=format&fit=crop&w=600&q=60'),
(7, 'Aquagym', 'https://images.unsplash.com/photo-1530549387789-4c1017266635?auto=format&fit=crop&w=600&q=60'),
(7, 'Estilo crol', 'https://images.unsplash.com/photo-1439405326854-014607f694d7?auto=format&fit=crop&w=600&q=60'),
(7, 'Brazadas de aletas', 'https://images.unsplash.com/photo-1530549387789-4c1017266635?auto=format&fit=crop&w=600&q=60'),
-- 8. Crossfit
(8, 'WOD del día', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=600&q=60'),
(8, 'Levantamiento olímpico', 'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?auto=format&fit=crop&w=600&q=60'),
(8, 'Rope training', 'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?auto=format&fit=crop&w=600&q=60'),
(8, 'Burpees intensos', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=600&q=60'),
(8, 'Entrenamiento HIIT', 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=600&q=60'),
-- 9. Senderismo
(9, 'Volcán al amanecer', 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=600&q=60'),
(9, 'Bosque nuboso', 'https://images.unsplash.com/photo-1501554728187-ce583db33af7?auto=format&fit=crop&w=600&q=60'),
(9, 'Ruta costera', 'https://images.unsplash.com/photo-1527004013197-933c4bb611b3?auto=format&fit=crop&w=600&q=60'),
(9, 'Caminata de altura', 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=600&q=60'),
(9, 'Sendero del río', 'https://images.unsplash.com/photo-1501554728187-ce583db33af7?auto=format&fit=crop&w=600&q=60'),
-- 10. Tenis
(10, 'Partido en cancha dura', 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=600&q=60'),
(10, 'Saque potente', 'https://images.unsplash.com/photo-1518655048521-f130df800f66?auto=format&fit=crop&w=600&q=60'),
(10, 'Punto de red', 'https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?auto=format&fit=crop&w=600&q=60'),
(10, 'Clase con coach', 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=600&q=60'),
(10, 'Tiebreak emocionante', 'https://images.unsplash.com/photo-1518655048521-f130df800f66?auto=format&fit=crop&w=600&q=60'),
-- 11. Voleibol
(11, 'Remate de punta', 'https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?auto=format&fit=crop&w=600&q=60'),
(11, 'Defensa de bloqueo', 'https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?auto=format&fit=crop&w=600&q=60'),
(11, 'Vóley playa', 'https://images.unsplash.com/photo-1519741347686-c1e0aadf4611?auto=format&fit=crop&w=600&q=60'),
(11, 'Pase de cola', 'https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?auto=format&fit=crop&w=600&q=60'),
(11, 'Saque flotante', 'https://images.unsplash.com/photo-1544457070-4cd450b52d38?auto=format&fit=crop&w=600&q=60'),
-- 12. Entrenamiento de fuerza
(12, 'Press de banca', 'https://images.unsplash.com/photo-1517963879433-6ad2b056d712?auto=format&fit=crop&w=600&q=60'),
(12, 'Sentadilla pesada', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=600&q=60'),
(12, 'Dominadas', 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=600&q=60'),
(12, 'Peso muerto', 'https://images.unsplash.com/photo-1517963879433-6ad2b056d712?auto=format&fit=crop&w=600&q=60'),
(12, 'Core en máquina', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=600&q=60'),
-- 13. Baile
(13, 'Salsa cubana', 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?auto=format&fit=crop&w=600&q=60'),
(13, 'Danza contemporánea', 'https://images.unsplash.com/photo-1535295972055-1c762f4483e5?auto=format&fit=crop&w=600&q=60'),
(13, 'Hip hop urbano', 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?auto=format&fit=crop&w=600&q=60'),
(13, 'Bachata sensual', 'https://images.unsplash.com/photo-1535295972055-1c762f4483e5?auto=format&fit=crop&w=600&q=60'),
(13, 'Ballet clásico', 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?auto=format&fit=crop&w=600&q=60'),
-- 14. Skateboarding
(14, 'Ollie básico', 'https://images.unsplash.com/photo-1520045892732-304bc3ac5d8e?auto=format&fit=crop&w=600&q=60'),
(14, 'Drop en rampa', 'https://images.unsplash.com/photo-1547447134-3e85a0a46222?auto=format&fit=crop&w=600&q=60'),
(14, 'Kickflip', 'https://images.unsplash.com/photo-1515863133900-3630cdb5cf61?auto=format&fit=crop&w=600&q=60'),
(14, 'Cruising en parque', 'https://images.unsplash.com/photo-1520045892732-304bc3ac5d8e?auto=format&fit=crop&w=600&q=60'),
(14, 'Board en el parque', 'https://images.unsplash.com/photo-1547447134-3e85a0a46222?auto=format&fit=crop&w=600&q=60'),
-- 15. Surf
(15, 'Ola de arena', 'https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=600&q=60'),
(15, 'Takeoff perfecto', 'https://images.unsplash.com/photo-1580894732444-8ecded7900cd?auto=format&fit=crop&w=600&q=60'),
(15, 'Surf de fin de semana', 'https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=600&q=60'),
(15, 'Paddle out', 'https://images.unsplash.com/photo-1580894732444-8ecded7900cd?auto=format&fit=crop&w=600&q=60'),
(15, 'Top turn', 'https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=600&q=60'),
-- 16. Escalada
(16, 'Boulder en roca', 'https://images.unsplash.com/photo-1522163182402-834f871fd851?auto=format&fit=crop&w=600&q=60'),
(16, 'Vía de cuerda', 'https://images.unsplash.com/photo-1507035895480-2b3156c31fc8?auto=format&fit=crop&w=600&q=60'),
(16, 'Escalada indoor', 'https://images.unsplash.com/photo-1522163182402-834f871fd851?auto=format&fit=crop&w=600&q=60'),
(16, 'Multi-largo', 'https://images.unsplash.com/photo-1507035895480-2b3156c31fc8?auto=format&fit=crop&w=600&q=60'),
(16, 'Escalada libre', 'https://images.unsplash.com/photo-1522163182402-834f871fd851?auto=format&fit=crop&w=600&q=60');