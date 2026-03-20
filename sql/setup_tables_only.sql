DROP DATABASE IF EXISTS Labs_project;
CREATE DATABASE Labs_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Labs_project;


CREATE TABLE IF NOT EXISTS Levels (
    LevelID   INTEGER PRIMARY KEY,
    LevelName TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS Staff (
    StaffID  INTEGER PRIMARY KEY,
    Name     VARCHAR(150) NOT NULL,
    JobTitle VARCHAR(150)
);

CREATE TABLE IF NOT EXISTS Modules (
    ModuleID       INTEGER PRIMARY KEY,
    ModuleName     VARCHAR(200) NOT NULL,
    ModuleLeaderID INTEGER,
    Description    TEXT,
    Image          VARCHAR(255),
    ImageAlt       VARCHAR(255),
    FOREIGN KEY (ModuleLeaderID) REFERENCES Staff(StaffID)
);

CREATE TABLE IF NOT EXISTS Programmes (
    ProgrammeID       INTEGER PRIMARY KEY AUTO_INCREMENT,
    ProgrammeName     VARCHAR(200) NOT NULL,
    LevelID           INTEGER,
    ProgrammeLeaderID INTEGER,
    Description       TEXT,
    Image             VARCHAR(255),
    ImageAlt          VARCHAR(255),
    IsPublished       TINYINT(1) DEFAULT 1,
    FOREIGN KEY (LevelID)           REFERENCES Levels(LevelID),
    FOREIGN KEY (ProgrammeLeaderID) REFERENCES Staff(StaffID)
);

CREATE TABLE IF NOT EXISTS ProgrammeModules (
    ProgrammeModuleID INTEGER PRIMARY KEY AUTO_INCREMENT,
    ProgrammeID       INTEGER,
    ModuleID          INTEGER,
    Year              INTEGER,
    FOREIGN KEY (ProgrammeID) REFERENCES Programmes(ProgrammeID),
    FOREIGN KEY (ModuleID)    REFERENCES Modules(ModuleID)
);

CREATE TABLE IF NOT EXISTS InterestedStudents (
    InterestID   INT AUTO_INCREMENT PRIMARY KEY,
    ProgrammeID  INT NOT NULL,
    StudentName  VARCHAR(100) NOT NULL,
    Email        VARCHAR(255) NOT NULL,
    RegisteredAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    IsActive     TINYINT(1) DEFAULT 1,
    UNIQUE KEY no_duplicates (ProgrammeID, Email),
    FOREIGN KEY (ProgrammeID) REFERENCES Programmes(ProgrammeID) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS AdminUsers (
    AdminID      INT AUTO_INCREMENT PRIMARY KEY,
    Username     VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role         ENUM('super','editor') DEFAULT 'editor'
);

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO Levels VALUES (1,'Undergraduate'),(2,'Postgraduate');

INSERT INTO Staff (StaffID, Name, JobTitle) VALUES
(1,'Dr. Alice Johnson','Professor of Computer Science'),
(2,'Dr. Brian Lee','Senior Lecturer in Mathematics'),
(3,'Dr. Carol White','Associate Professor of Systems'),
(4,'Dr. David Green','Lecturer in Databases'),
(5,'Dr. Emma Scott','Professor of Software Engineering'),
(6,'Dr. Frank Moore','Senior Lecturer in Algorithms'),
(7,'Dr. Grace Adams','Lecturer in Cyber Security'),
(8,'Dr. Henry Clark','Professor of Artificial Intelligence'),
(9,'Dr. Irene Hall','Associate Professor of Machine Learning'),
(10,'Dr. James Wright','Lecturer in Ethical Hacking'),
(11,'Dr. Sophia Miller','Professor of Advanced AI'),
(12,'Dr. Benjamin Carter','Senior Lecturer in Cyber Threats'),
(13,'Dr. Chloe Thompson','Lecturer in Big Data'),
(14,'Dr. Daniel Robinson','Associate Professor of Cloud Computing'),
(15,'Dr. Emily Davis','Senior Lecturer in Software Design'),
(16,'Dr. Nathan Hughes','Lecturer in AI Ethics'),
(17,'Dr. Olivia Martin','Associate Professor of Quantum Computing'),
(18,'Dr. Samuel Anderson','Lecturer in Cybersecurity Law'),
(19,'Dr. Victoria Hall','Professor of Neural Networks'),
(20,'Dr. William Scott','Lecturer in Human-AI Interaction');

INSERT INTO Modules (ModuleID,ModuleName,ModuleLeaderID,Description,ImageAlt) VALUES
(1,'Introduction to Programming',1,'Covers the fundamentals of programming using Python and Java.','Students coding on laptops'),
(2,'Mathematics for Computer Science',2,'Teaches discrete mathematics, linear algebra, and probability theory.','Equations on a whiteboard'),
(3,'Computer Systems & Architecture',3,'Explores CPU design, memory management, and assembly language.','Circuit board close-up'),
(4,'Databases',4,'Covers SQL, relational database design, and NoSQL systems.','Data tables diagram'),
(5,'Software Engineering',5,'Focuses on agile development, design patterns, and project management.','Team planning on a board'),
(6,'Algorithms & Data Structures',6,'Examines sorting, searching, graphs, and complexity analysis.','Flowchart on paper'),
(7,'Cyber Security Fundamentals',7,'Provides an introduction to network security, cryptography, and vulnerabilities.','Padlock over network graphic'),
(8,'Artificial Intelligence',8,'Introduces AI concepts such as neural networks, expert systems, and robotics.','Robot arm in a lab'),
(9,'Machine Learning',9,'Explores supervised and unsupervised learning, including decision trees and clustering.','Data visualisation chart'),
(10,'Ethical Hacking',10,'Covers penetration testing, security assessments, and cybersecurity laws.','Hacker at keyboard'),
(11,'Computer Networks',1,'Teaches TCP/IP, network layers, and wireless communication.','Network cables and switch'),
(12,'Software Testing & Quality Assurance',2,'Focuses on automated testing, debugging, and code reliability.','Code on monitor'),
(13,'Embedded Systems',3,'Examines microcontrollers, real-time OS, and IoT applications.','Microcontroller board'),
(14,'Human-Computer Interaction',4,'Studies UI/UX design, usability testing, and accessibility.','Person using touch screen'),
(15,'Blockchain Technologies',5,'Covers distributed ledgers, consensus mechanisms, and smart contracts.','Chain of data blocks'),
(16,'Cloud Computing',6,'Introduces cloud services, virtualisation, and distributed systems.','Cloud infrastructure diagram'),
(17,'Digital Forensics',7,'Teaches forensic investigation techniques for cybercrime.','Forensic analyst at computer'),
(18,'Final Year Project',8,'A major independent project where students develop a software solution.','Student presenting project'),
(19,'Advanced Machine Learning',11,'Covers deep learning, reinforcement learning, and cutting-edge AI techniques.','Neural network diagram'),
(20,'Cyber Threat Intelligence',12,'Focuses on cybersecurity risk analysis, malware detection, and threat mitigation.','Threat monitoring dashboard'),
(21,'Big Data Analytics',13,'Explores data mining, distributed computing, and AI-driven insights.','Big data visualisation'),
(22,'Cloud & Edge Computing',14,'Examines scalable cloud platforms, serverless computing, and edge networks.','Server racks in data centre'),
(23,'Blockchain & Cryptography',15,'Covers decentralised applications, consensus algorithms, and security measures.','Cryptographic key graphic'),
(24,'AI Ethics & Society',16,'Analyses ethical dilemmas in AI, fairness, bias, and regulatory considerations.','Scales of justice'),
(25,'Quantum Computing',17,'Introduces quantum algorithms, qubits, and cryptographic applications.','Quantum computer chip'),
(26,'Cybersecurity Law & Policy',18,'Explores digital privacy, GDPR, and international cyber law.','Legal documents and gavel'),
(27,'Neural Networks & Deep Learning',19,'Delves into convolutional networks, GANs, and AI advancements.','Brain network graphic'),
(28,'Human-AI Interaction',20,'Studies AI usability, NLP systems, and social robotics.','Human and robot shaking hands'),
(29,'Autonomous Systems',11,'Focuses on self-driving technology, robotics, and intelligent agents.','Self-driving car sensor view'),
(30,'Digital Forensics & Incident Response',12,'Teaches forensic analysis, evidence gathering, and threat mitigation.','Analyst reviewing evidence'),
(31,'Postgraduate Dissertation',13,'A major research project where students explore advanced topics in computing.','Student writing dissertation');

INSERT INTO Programmes (ProgrammeName,LevelID,ProgrammeLeaderID,Description,IsPublished) VALUES
('BSc Computer Science',1,1,'A broad computer science degree covering programming, AI, cybersecurity, and software engineering.',1),
('BSc Software Engineering',1,2,'A specialised degree focusing on the development and lifecycle of software applications.',1),
('BSc Artificial Intelligence',1,3,'Focuses on machine learning, deep learning, and AI applications.',1),
('BSc Cyber Security',1,4,'Explores network security, ethical hacking, and digital forensics.',1),
('BSc Data Science',1,5,'Covers big data, machine learning, and statistical computing.',1),
('MSc Machine Learning',2,11,'A postgraduate degree focusing on deep learning, AI ethics, and neural networks.',1),
('MSc Cyber Security',2,12,'A specialised programme covering digital forensics, cyber threat intelligence, and security policy.',1),
('MSc Data Science',2,13,'Focuses on big data analytics, cloud computing, and AI-driven insights.',1),
('MSc Artificial Intelligence',2,14,'Explores autonomous systems, AI ethics, and deep learning technologies.',1),
('MSc Software Engineering',2,15,'Emphasises software design, blockchain applications, and cutting-edge methodologies.',1);

INSERT INTO ProgrammeModules (ProgrammeID,ModuleID,Year) VALUES
(1,1,1),(1,2,1),(1,3,1),(1,4,1),
(2,1,1),(2,2,1),(2,3,1),(2,4,1),
(3,1,1),(3,2,1),(3,3,1),(3,4,1),
(4,1,1),(4,2,1),(4,3,1),(4,4,1),
(5,1,1),(5,2,1),(5,3,1),(5,4,1),
(1,5,2),(1,6,2),(1,7,2),(1,8,2),
(2,5,2),(2,6,2),(2,12,2),(2,14,2),
(3,5,2),(3,9,2),(3,8,2),(3,10,2),
(4,7,2),(4,10,2),(4,11,2),(4,17,2),
(5,5,2),(5,6,2),(5,9,2),(5,16,2),
(1,11,3),(1,13,3),(1,15,3),(1,18,3),
(2,13,3),(2,15,3),(2,16,3),(2,18,3),
(3,13,3),(3,15,3),(3,16,3),(3,18,3),
(4,15,3),(4,16,3),(4,17,3),(4,18,3),
(5,9,3),(5,14,3),(5,16,3),(5,18,3),
(6,19,1),(6,24,1),(6,27,1),(6,29,1),(6,31,1),
(7,20,1),(7,26,1),(7,30,1),(7,23,1),(7,31,1),
(8,21,1),(8,22,1),(8,27,1),(8,28,1),(8,31,1),
(9,19,1),(9,24,1),(9,28,1),(9,29,1),(9,31,1),
(10,23,1),(10,22,1),(10,25,1),(10,26,1),(10,31,1);

INSERT INTO InterestedStudents (ProgrammeID,StudentName,Email) VALUES
(1,'John Doe','john.doe@example.com'),
(4,'Jane Smith','jane.smith@example.com'),
(6,'Alex Brown','alex.brown@example.com'),
(9,'Priya Patel','priya.patel@example.com'),
(2,'Tom Wilson','tom.wilson@example.com'),
(3,'Sara Ahmed','sara.ahmed@example.com');

-- Admin password is set automatically by login.php on first visit
-- Username: admin  |  Password: Admin1234
INSERT INTO AdminUsers (Username, PasswordHash, Role)
VALUES ('admin', 'RESET_ON_FIRST_LOGIN', 'super');

CREATE INDEX idx_prog_level     ON Programmes(LevelID);
CREATE INDEX idx_prog_published ON Programmes(IsPublished);
CREATE INDEX idx_pm_prog        ON ProgrammeModules(ProgrammeID);
CREATE INDEX idx_pm_mod         ON ProgrammeModules(ModuleID);
CREATE INDEX idx_stu_prog       ON InterestedStudents(ProgrammeID);
CREATE INDEX idx_stu_active     ON InterestedStudents(IsActive);
