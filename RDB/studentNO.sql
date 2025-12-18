-- Create Participant Table
CREATE TABLE Participant (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Name VARCHAR(100) NOT NULL,
    BestRecord VARCHAR(20),
    Nationality VARCHAR(50),
    PassportNO VARCHAR(50),
    Sex VARCHAR(10) NOT NULL,
    Age INT NOT NULL,
    Email VARCHAR(100) NOT NULL,
    Phone VARCHAR(20) NOT NULL,
    Address TEXT,
    Role VARCHAR(10) DEFAULT 'user' -- 'admin' or 'user'
);

-- Create Marathon Table
CREATE TABLE Marathon (
    MarathonID INT PRIMARY KEY AUTO_INCREMENT,
    RaceName VARCHAR(100),
    Date DATE,
    Status VARCHAR(20) DEFAULT 'Scheduled',
    CancelReason VARCHAR(255) DEFAULT NULL -- Stores custom cancel reason
);

-- Create Participate Table (Junction)
CREATE TABLE Participate (
    MarathonID INT,
    UserID INT,
    EntryNO VARCHAR(50) DEFAULT NULL,
    Hotel VARCHAR(100) DEFAULT NULL,
    TimeRecord VARCHAR(20) DEFAULT NULL,
    Standings INT DEFAULT NULL,
    PRIMARY KEY (MarathonID, UserID),
    FOREIGN KEY (MarathonID) REFERENCES Marathon(MarathonID),
    FOREIGN KEY (UserID) REFERENCES Participant(UserID)
);

-- SAMPLE DATA (Required for demo)
-- 1. Create Admin (Pass: 123)
INSERT INTO Participant (Username, Password, Name, BestRecord, Nationality, PassportNO, Sex, Age, Email, Phone, Address, Role) 
VALUES ('admin', '$2y$10$bU6PMnAzAPyA.5E7zaIEzu/p5IJno5lXDHmeQJJ6Gk005buydwyUa', 'Admin System', NULL, 'Vietnam', 'A1234567', 'Male', 35, 'admin@hanoimarathon.vn', '0901234567', 'Hanoi, Vietnam', 'admin');

-- 2. Create Regular User (Pass: 123)
INSERT INTO Participant (Username, Password, Name, BestRecord, Nationality, PassportNO, Sex, Age, Email, Phone, Address, Role) 
VALUES ('user1', '$2y$10$Ppzmsn/GV8O3/DP8aFw9GONnnIqGDWeu8r4dZQd2H1wuUd9ZfQ9Y2', 'Nguyen Van A', '03:45:22', 'Vietnam', 'B9876543', 'Male', 28, 'nguyenvana@email.com', '0987654321', '123 Hoang Hoa Tham, Hanoi', 'user');

-- 3. Create 2 Sample Marathons
INSERT INTO Marathon (RaceName, Date) VALUES ('Hanoi Marathon 2025', '2025-12-18');
INSERT INTO Marathon (RaceName, Date) VALUES ('Da Nang Beach Run', '2026-06-15');