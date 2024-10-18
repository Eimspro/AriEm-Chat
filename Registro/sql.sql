CREATE TABLE usuarios (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,  
    name VARCHAR(100) NOT NULL,             
    phone VARCHAR(20) NOT NULL,             
    email VARCHAR(100) NOT NULL,            
    UNIQUE (phone),                          
    UNIQUE (email)                           
);
Aca puede ver la Estructura de la tabla
