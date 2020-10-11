CREATE TABLE af_currency_rates (
	ID TINYINT auto_increment,
	Name VARCHAR(12) NOT NULL,
	Rate DECIMAL(6,3) NOT NULL,
	PRIMARY KEY (ID)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_czech_ci;

INSERT INTO `af_currency_rates` (
`ID` ,
`Name` ,
`Rate`
)
VALUES (
NULL , 'USDCZK', '22.668'
);