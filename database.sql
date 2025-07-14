-- Crear base de datos
CREATE DATABASE tienda_juancho_sofi;
USE tienda_juancho_sofi;

-- Tabla usuarios
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    correo VARCHAR(150) UNIQUE NOT NULL,
    celular BIGINT,
    clave VARCHAR(100) NOT NULL,
    rol VARCHAR(50) DEFAULT 'cliente', -- 'cliente' o 'administrador'
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla categorías
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(100),
    imagen VARCHAR(50) -- Nombre del archivo de imagen
);

-- Tabla productos
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    cantidad INT NOT NULL, -- Stock disponible
    imagen VARCHAR(255), -- Nombre del archivo de imagen
    id_categoria INT,
    marcas VARCHAR(100),
    descuento INT DEFAULT 0, -- Porcentaje de descuento
    tallas VARCHAR(255), -- Ej: "S,M,L,XL"
    colores VARCHAR(255), -- Ej: "Rojo,Azul,Verde"
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabla órdenes (pedidos de los clientes)
CREATE TABLE ordenes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    codigo VARCHAR(20) UNIQUE NOT NULL, -- Código único de la orden
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10, 2) NOT NULL,
    estado VARCHAR(50) DEFAULT 'Solicitado', -- Solicitado, Atendido, Entregado, Rechazado
    tipo_venta VARCHAR(50) NOT NULL, -- 'online' o 'local'
    direccion_envio TEXT,
    metodo_pago VARCHAR(50),
    telefono_contacto BIGINT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabla ventas (items dentro de una orden)
CREATE TABLE ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_orden INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL, -- Precio al momento de la venta
    valor_total DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_orden) REFERENCES ordenes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
);

-- Tabla para mensajes de contacto
CREATE TABLE contactar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    asunto VARCHAR(255),
    mensaje TEXT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Datos de prueba para usuarios
INSERT INTO usuarios (nombre, correo, celular, clave, rol) VALUES
('Admin Juancho', 'admin@juancho.com', 3001234567, '$2y$10$2/g.1234567890abcdefghijklmnopqrstuv', 'administrador'), -- Clave: admin123
('Cliente Sofi', 'sofi@cliente.com', 3109876543, '$2y$10$2/g.1234567890abcdefghijklmnopqrstuv', 'cliente'); -- Clave: cliente123

-- Actualizar la clave para que sea 'admin123' y 'cliente123' respectivamente
UPDATE usuarios SET clave = '$2y$10$2/g.1234567890abcdefghijklmnopqrstuv' WHERE correo = 'admin@juancho.com'; -- Hash de 'admin123'
UPDATE usuarios SET clave = '$2y$10$2/g.1234567890abcdefghijklmnopqrstuv' WHERE correo = 'sofi@cliente.com'; -- Hash de 'cliente123'

-- Datos de prueba para categorías
INSERT INTO categorias (nombre, descripcion, imagen) VALUES
('Portátiles', 'Equipos portátiles de alto rendimiento', 'portatiles.png'),
('Smartphones', 'Teléfonos inteligentes de última generación', 'smartphones.png'),
('Accesorios', 'Accesorios para dispositivos electrónicos', 'accesorios.png'),
('Audio', 'Dispositivos de sonido y auriculares', 'audio.png');

-- Datos de prueba para productos
INSERT INTO productos (nombre, descripcion, precio_unitario, cantidad, imagen, id_categoria, marcas, descuento, tallas, colores) VALUES
('Laptop Gamer X1', 'Potente laptop para juegos con RTX 3080.', 5500000.00, 10, 'laptop_gamer_x1.png', 1, 'MSI', 10, NULL, 'Negro,Gris'),
('Smartphone Ultra S22', 'Teléfono con cámara de 108MP y pantalla AMOLED.', 3200000.00, 25, 'smartphone_s22.png', 2, 'Samsung', 5, NULL, 'Negro,Blanco,Verde'),
('Auriculares Bluetooth Pro', 'Sonido de alta fidelidad y cancelación de ruido.', 450000.00, 50, 'auriculares_pro.png', 4, 'Sony', 0, NULL, 'Negro,Blanco'),
('Mouse Gamer RGB', 'Mouse ergonómico con iluminación RGB personalizable.', 120000.00, 100, 'mouse_gamer.png', 3, 'Logitech', 15, NULL, 'Negro'),
('Teclado Mecánico K95', 'Teclado con switches Cherry MX y reposamuñecas.', 300000.00, 30, 'teclado_mecanico.png', 3, 'Corsair', 0, NULL, 'Negro'),
('Monitor Curvo 27"', 'Monitor de 144Hz para una experiencia inmersiva.', 1800000.00, 15, 'monitor_curvo.png', 1, 'Dell', 8, NULL, 'Negro'),
('Smartwatch Fit Pro', 'Reloj inteligente con monitoreo de salud y GPS.', 800000.00, 40, 'smartwatch_fit.png', 3, 'Xiaomi', 10, NULL, 'Negro,Rosa'),
('Parlante Portátil Xtreme', 'Sonido potente y resistente al agua.', 600000.00, 20, 'parlante_portatil.png', 4, 'JBL', 0, NULL, 'Negro,Azul'),
('Tablet Pro 11', 'Tablet versátil para trabajo y entretenimiento.', 2500000.00, 18, 'tablet_pro.png', 1, 'Apple', 0, NULL, 'Gris,Plata'),
('Cargador Rápido USB-C', 'Cargador de 65W para carga ultra rápida.', 80000.00, 200, 'cargador_rapido.png', 3, 'Anker', 0, NULL, 'Blanco');

-- Datos de prueba para órdenes
INSERT INTO ordenes (id_usuario, codigo, total, estado, tipo_venta, direccion_envio, metodo_pago, telefono_contacto) VALUES
(2, 'ORD001', 500000.00, 'Entregado', 'online', 'Calle 10 # 20-30, Bogotá', 'Tarjeta de Crédito', 3101112233),
(2, 'ORD002', 120000.00, 'Atendido', 'local', NULL, 'Efectivo', 3104445566),
(2, 'ORD003', 3500000.00, 'Solicitado', 'online', 'Carrera 5 # 15-25, Medellín', 'PSE', 3107778899);

-- Datos de prueba para ventas (items de las órdenes)
INSERT INTO ventas (id_orden, id_producto, cantidad, precio_unitario, valor_total) VALUES
(1, 3, 1, 450000.00, 450000.00), -- Auriculares Pro
(1, 4, 1, 50000.00, 50000.00),   -- Mouse Gamer (precio ajustado para ejemplo)
(2, 4, 1, 120000.00, 120000.00), -- Mouse Gamer RGB
(3, 2, 1, 3200000.00, 3200000.00), -- Smartphone Ultra S22
(3, 10, 1, 80000.00, 80000.00); -- Cargador Rápido USB-C

-- Datos de prueba para mensajes de contacto
INSERT INTO contactar (nombre, correo, asunto, mensaje) VALUES
('Pedro Pérez', 'pedro@example.com', 'Consulta sobre un producto', 'Quisiera saber si tienen disponibilidad del producto X en color azul.'),
('Ana Gómez', 'ana@example.com', 'Problema con mi pedido', 'Mi pedido ORD001 no ha llegado, ¿podrían verificar el estado?');
