create database IF NOT EXISTS `baseDatos1`;
use `baseDatos1`;

create table IF NOT EXISTS `tbperfil` (
  `tbperfilid` int NOT NULL AUTO_INCREMENT,
  `tbperfilnombre` text NOT NULL,
  `tbperfilcontra` text NOT NULL,
  `tbperfilcorreo`  text NOT NULL,
  PRIMARY KEY (`tbperfilid`)
);

insert into `tbperfil` (`tbperfilnombre`, `tbperfilcontra`, `tbperfilcorreo`) values
('Keylor', '23122005', 'keylor@example.com'),
('Tayson', '1234567', 'tayson@example.com'),
('Andres', '2525', 'andres@example.com');


