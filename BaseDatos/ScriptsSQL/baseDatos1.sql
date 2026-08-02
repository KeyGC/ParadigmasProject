create database IF NOT EXISTS `baseDatos1`;
use `baseDatos1`;

create table IF NOT EXISTS `perfil` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nickname` varchar(50) NOT NULL unique,
  `password` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
);

insert into `perfil` (`nickname`, `password`) values
('Keylor', '23122005'),
('Tayson', '1234567'),
('Andres', '2525');

