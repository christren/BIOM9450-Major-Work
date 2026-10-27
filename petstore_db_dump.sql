-- MySQL dump 10.13  Distrib 9.4.0, for macos15.4 (arm64)
--
-- Host: localhost    Database: petstore_db
-- ------------------------------------------------------
-- Server version	9.4.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Animal`
--

DROP TABLE IF EXISTS `Animal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Animal` (
  `AnimalID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) DEFAULT NULL,
  `Category` varchar(50) DEFAULT NULL,
  `Breed` varchar(50) DEFAULT NULL,
  `DateBorn` date DEFAULT NULL,
  `Gender` enum('Male','Female','Unknown') DEFAULT NULL,
  `Registered` varchar(10) DEFAULT NULL,
  `Color` varchar(30) DEFAULT NULL,
  `ListPrice` float DEFAULT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`AnimalID`),
  UNIQUE KEY `AnimalID_UNIQUE` (`AnimalID`)
) ENGINE=InnoDB AUTO_INCREMENT=202 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Animal`
--

LOCK TABLES `Animal` WRITE;
/*!40000 ALTER TABLE `Animal` DISABLE KEYS */;
INSERT INTO `Animal` VALUES (2,NULL,'Fish','Angel','1996-05-05','Male',NULL,'Black',12,NULL),(4,'Gary','Dog','Labrador Retriever','1996-03-02','Male','AKC','Spotted',174.07,NULL),(5,NULL,'Fish','Shark','1996-01-01','Female',NULL,'Gray',22,NULL),(6,'Rosie','Cat','Oriental Shorthair','1996-08-02','Female','CFA','Gray',193.318,NULL),(7,'Eugene','Cat','Bombay','1996-01-25','Male','CFA','Black',279.542,NULL),(8,'Miranda','Dog','Norfolk Terrier','1996-05-04','Female','AKC','Red',203.754,NULL),(9,'Wayne','Fish','Guppy','1996-03-10','Male',NULL,'Gold',2,'net.ucanaccess.jdbc.UcanaccessBlob@7a791b66'),(10,'Sherri','Dog','Siberian Huskie','1996-09-13','Female','AKC','Black/White',166.791,NULL),(11,'Susan','Dog','Dalmation','1996-01-22','Female','AKC','Spotted',164.964,NULL),(12,'Leisha','Dog','Rottweiler','1996-06-13','Female','AKC','Brown',164.063,NULL),(13,NULL,'Fish','Tetra','1996-03-01','Male',NULL,'Red',12,NULL),(14,'Tina','Cat','Sphynx','1996-01-06','Female','CFA','Gold',143.946,NULL),(15,'Bonita','Dog','Cocker Spaniel','1996-10-21','Female','AKC','Gold',284.851,NULL),(16,'Lisa','Dog','Great Pyrenees','1996-01-05','Female','AKC','Brown',274.353,NULL),(17,'Jackie','Dog','Doberman','1996-04-12','Male','AKC','Black',165.739,NULL),(18,'Allison','Cat','Manx','1996-04-21','Female','CFA','Black',215.928,NULL),(19,'Gene','Dog','Wire Fox Terrier','1996-05-11','Male','AKC','White',210.656,NULL),(20,'Buck','Cat','Bombay','1996-04-30','Male','CFA','Black',213.188,NULL),(21,'Debbie','Bird','Cockatiel','1996-01-14','Female',NULL,'Blue',177.89,NULL),(23,'Craig','Dog','Poodle','1996-03-17','Male','AKC','White',108.326,NULL),(24,'Carla','Cat','Maine Coon','1996-04-11','Female','CFA','Brown\\Red',163.974,NULL),(25,'Vivian','Dog','Cocker Spaniel','1996-04-24','Female',NULL,'Red',128.188,NULL),(26,'Orville','Dog','Shetland Sheepdog','1996-07-06','Male','AKC','Gray',242.001,NULL),(27,NULL,'Reptile','Gila Monster','1996-03-11','Male',NULL,'Green',192.089,NULL),(28,NULL,'Spider','Tarantula','1996-02-07','Male',NULL,'Black',45,NULL),(29,NULL,'Fish','Snail','1996-06-15','Male',NULL,'White',15,NULL),(30,'Golda','Dog','Bloodhound','1996-01-06','Female','AKC','Brown',187.621,NULL),(31,'Taylor','Dog','Mastiff','1996-02-15','Male','AKC','Brown',164.7,NULL),(32,'Cathy','Cat','Scottish Fold','1996-06-05','Female','CFA','White',138.917,NULL),(33,'Kent','Dog','Poodle-Toy','1996-05-30','Male','AKC','Gray/Blue',122.366,NULL),(34,'Rhonda','Dog','Maltipoos','1996-07-16','Female','AKC','Gray',199.497,NULL),(35,'Charles','Bird','Parakeet','1996-04-09','Male',NULL,'Blue/Gold',203.107,NULL),(36,'Connie','Dog','Poodle-Toy','1996-10-14','Female','AKC','White',190.461,NULL),(37,'Hunter','Dog','Bassett Hound','1996-05-04','Male','AKC','Brown',115.398,NULL),(39,'Doris','Dog','Rottweiler','1996-03-27','Female','AKC','Red',211.284,NULL),(40,'Hunter','Dog','Weimaraner','1996-04-02','Male','AKC','Brown',214.826,NULL),(41,'Claudia','Dog','Boston Terrier','1996-01-05','Female','AKC','Black',179.928,NULL),(42,'Lloyd','Dog','Bassett Hound','1996-05-19','Male','AKC','Brown/Black',193.635,NULL),(43,'Chelsea','Reptile','Python','1996-02-10','Female',NULL,'Black/Gold',237.909,NULL),(44,'Melanie','Dog','Mastiff','1996-06-18','Female','AKC','Black',141.17,NULL),(45,'Bernard','Cat','American Shorthair','1996-08-08','Male','CFA','Gray',182.774,NULL),(46,'Lou Ann','Dog','Chinese Shar Pei','1996-01-08','Female',NULL,'Gold',254.855,NULL),(47,'Brian','Dog','Cock A Poo','1996-03-03','Male','AKC','Black',206.073,NULL),(48,'Anthony','Cat','Japanese Bobtail','1996-06-16','Male','CFA','White',226.541,NULL),(49,'Martha','Cat','Korat','1996-03-16','Female',NULL,'Gray/Blue',218.958,NULL),(50,'Arnold','Dog','Pomeranian','1996-05-18','Male','AKC','Gray/Blue',267.053,NULL),(51,'Kelly','Dog','Golden Retriever','1996-01-16','Male',NULL,'Gold',246.053,NULL),(52,'Buck','Dog','St Bernard','1996-01-01','Male','AKC','Brown/White',231.443,NULL),(53,NULL,'Fish','Starfish','1996-06-23','Female',NULL,'Blue',52,NULL),(54,'Sandy','Bird','Parrot','1996-01-23','Female',NULL,'Blue/Red',107.528,NULL),(55,'Alice','Dog','Cairn Terrier','1996-01-03','Female','AKC','Gray/Blue',204.721,NULL),(56,'Lillie','Cat','Abyssinian','1996-04-03','Female','CFA','White',227.399,NULL),(57,'Darcy','Dog','Akita','1996-01-02','Male','AKC','White/Black',237.104,NULL),(58,'Curtis','Bird','Parakeet','1996-04-28','Male',NULL,'Gold/Red',146.877,NULL),(59,'Gaylen','Dog','Dalmation','1996-05-10','Male','AKC','Spotted',199.52,NULL),(60,'Everett','Dog','Australian Shepherd','1996-05-20','Male','AKC','Brown/White',325.696,NULL),(61,'Sandy','Dog','Chinese Shar Pei','1996-06-01','Female',NULL,'Brown',221.554,NULL),(62,'Linda','Cat','Russian Blue','1996-08-22','Female','CFA','Gray/Blue',96.7374,NULL),(63,'Sandy','Cat','Persian','1996-02-19','Female','CFA','White',191.276,NULL),(64,NULL,'Reptile','Python','1996-04-11','Female',NULL,'Black',255.251,NULL),(65,'Bryce','Cat','Persian','1996-06-23','Male','CFA','White/Black',133.244,NULL),(66,'Seth','Cat','Russian Blue','1996-01-09','Male','CFA','Gray/Blue',275.106,NULL),(67,'Ara','Cat','Manx','1996-01-29','Male','CFA','White',179.056,NULL),(68,'Hoyt','Bird','Parrot','1996-03-28','Male',NULL,'Blue/Red',235.273,NULL),(69,'Inez','Dog','Border Collie','1996-02-24','Female','AKC','Brown/White',104.881,NULL),(70,'Chris','Dog','Akita','1996-02-08','Male',NULL,'White/Black',206.579,NULL),(71,'Cora','Dog','Keeshond','1996-10-02','Female','AKC','Brown',119.673,NULL),(72,NULL,'Mammal','Hamster','1996-06-27','Male',NULL,'Brown/White',13,NULL),(73,'Janet','Dog','Coon Hound','1996-02-24','Female','AKC','Brown',123.653,NULL),(74,'Gina','Dog','Lhasa Apso','1996-01-25','Female','AKC','Brown/White',128.086,NULL),(75,'Ella','Dog','Chinese Shar Pei','1996-02-08','Female','AKC','Brown',131.833,NULL),(77,'Damon','Cat','Bombay','1996-01-17','Male','CFA','Black',178.165,NULL),(78,'Hunter','Dog','Great Dane','1996-01-02','Male','AKC','Brown',215.745,NULL),(79,NULL,'Bird','Cockatiel','1996-02-26','Male',NULL,'Brown/Blue',224.478,NULL),(80,'Oakley','Dog','Jack Russell Terrier','1996-08-09','Female','AKC','Brown/White',84.4571,NULL),(81,NULL,'Bird','Canary','1996-07-29','Female',NULL,'Yellow',229.442,NULL),(82,NULL,'Mammal','Ferret','1996-07-21','Male',NULL,'Brown',125,NULL),(83,'Laura','Dog','Chihuahua','1996-01-25','Female','AKC','Brown',155.466,NULL),(84,'Patricia','Cat','Angora','1996-02-26','Female',NULL,'White',294.264,NULL),(85,'Dwight','Dog','Dachshund','1996-03-03','Male','AKC','Brown',178.65,NULL),(86,NULL,'Fish','Tetra','1996-01-02','Female',NULL,'Black',22,NULL),(87,'Christine','Dog','Norfolk Terrier','1996-08-14','Female','AKC','Red',208.124,NULL),(88,'Brandy','Dog','Cocker Spaniel','1996-07-09','Female','AKC','Brown',189.066,NULL),(89,'Ben','Dog','German Shepherd','1996-01-10','Male',NULL,'Black/White',153.941,NULL),(90,'Chad','Dog','Great Dane','1996-06-15','Male',NULL,'Black',223.971,NULL),(91,'Russell','Dog','Pit Bull','1996-05-23','Male','AKC','Brown',171.597,NULL),(92,'Terry','Bird','Lovebird','1996-02-04','Male',NULL,'White',180.166,NULL),(93,'Trina','Dog','Greyhound','1996-01-05','Female','AKC','Gray',143.077,NULL),(94,'Jean','Dog','Siberian Huskie','1996-07-01','Female','AKC','White/Brown',223.38,NULL),(95,'Ashley','Cat','Sphynx','1996-01-25','Female','CFA','White',183.485,NULL),(96,'Stan','Cat','Sphynx','1996-01-01','Male','CFA','Black',241.675,NULL),(97,'Ernest','Dog','Dachshund','1996-01-21','Male','AKC','Black',141.95,NULL),(98,'Ricardo','Dog','Bulldog','1996-02-03','Male','AKC','Black',177.203,NULL),(99,NULL,'Mammal','Ferret','1996-07-27','Male',NULL,'Black',230.898,NULL),(100,'Bill','Cat','Scottish Fold','1996-08-06','Male','CFA','Gray',132.797,NULL),(101,'Bessie','Dog','Australian Shepherd','1996-01-03','Female','AKC','White/Brown',216.1,NULL),(102,'Leonard','Dog','Mastiff','1996-09-18','Male','AKC','Brown',197.32,NULL),(103,'Lee','Cat','Siamese','1996-07-17','Male','CFA','White/Black',232.908,NULL),(104,'Julie','Cat','Longhair','1996-02-18','Female','CFA','Yellow',180.388,NULL),(105,'Beth','Dog','Akita','1996-02-06','Female','AKC','White/Brown',177.068,NULL),(106,'Taylor','Cat','Oriental Shorthair','1996-02-21','Male','CFA','Yellow/Black',220.943,NULL),(108,'Anissa','Dog','Bloodhound','1996-04-04','Female','AKC','Brown',169.139,NULL),(109,NULL,'Fish','Angel','1996-04-05','Female',NULL,'Yellow',128.221,NULL),(110,'Cathy','Bird','African Grey','1996-07-05','Female',NULL,'Gray/Red',192.773,NULL),(111,'Martin','Dog','Keeshond','1996-03-08','Male','AKC','Black',238.552,NULL),(112,'Elizabeth','Cat','Persian','1996-01-15','Female','CFA','Gray',145.34,NULL),(113,'Courtney','Dog','Boston Terrier','1996-03-12','Female','AKC','Black',142.618,NULL),(115,'Jewell','Cat','Korat','1996-01-15','Female','CFA','Black',210.88,NULL),(116,NULL,'Bird','Parrot','1996-06-01','Male',NULL,'Blue/Gold',133.012,NULL),(117,'Wallace','Cat','Other','1996-08-09','Male','CFA','Brown',50,NULL),(118,NULL,'Fish','Guppy','1996-05-05','Female',NULL,'Brown',4,NULL),(119,'DuJuan','Reptile','Boa Constrictor','1996-04-26','Male',NULL,'Brown/Gold',202.566,NULL),(120,'Chester','Dog','Keeshond','1996-02-25','Male','AKC','Black',216.919,NULL),(121,'Rachel','Dog','Norfolk Terrier','1996-08-02','Female','AKC','Gray',284.545,NULL),(122,'Gayle','Cat','Bombay','1996-02-14','Female','CFA','Black',282.841,NULL),(124,'Trina','Cat','Oriental Shorthair','1996-05-25','Female','CFA','Gray',187.703,NULL),(125,'Harlen','Dog','Cocker Spaniel','1996-03-17','Male','AKC','Yellow',193.083,NULL),(126,'Pierre','Cat','Maine Coon','1996-04-11','Male','CFA','Brown/Yellow',189.272,NULL),(127,'Beth','Cat','British Blue','1996-01-09','Female','CFA','Gray/Blue',195.725,NULL),(128,'Henry','Dog','German Shepherd','1996-07-16','Male','AKC','Black/White',155.956,NULL),(129,NULL,'Fish','Snail','1996-08-02','Male',NULL,'Yellow',16,NULL),(130,'Darrell','Dog','Greyhound','1996-07-14','Male','AKC','Gray',157.835,NULL),(131,'Elvis','Dog','Mastiff','1996-03-11','Male','AKC','Black',214.395,NULL),(132,'Dennis','Cat','Angora','1996-07-13','Male',NULL,'White',157.011,NULL),(133,'Anissa','Cat','Canadian Hairless','1996-07-13','Female','CFA','Gray',328.18,NULL),(134,NULL,'Reptile','Gila Monster','1996-02-08','Male',NULL,'Brown',182.363,NULL),(135,NULL,'Mammal','Ferret','1996-07-24','Female',NULL,'Brown',125.238,NULL),(136,NULL,'Bird','Other','1996-08-11','Male',NULL,'White',206.776,NULL),(137,NULL,'Fish','Shark','1996-01-24','Female',NULL,'Gray',238.527,NULL),(138,'Owen','Cat','Japanese Bobtail','1996-05-21','Male','CFA','White',232.468,NULL),(139,'Claudia','Dog','Doberman','1996-08-02','Female','AKC','Black',220.976,NULL),(140,'Cal','Cat','Persian','1996-07-21','Male','CFA','White',134.807,NULL),(141,'MaryJo','Dog','Pit Bull','1996-06-22','Female','AKC','Brown',234.512,NULL),(142,'Susan','Dog','Siberian Huskie','1996-08-03','Female','AKC','White',180.934,NULL),(143,'Leisha','Cat','Korat','1996-05-20','Female','CFA','Brown',163.07,NULL),(144,'Dewey','Dog','Poodle-Toy','1996-05-07','Male','AKC','Black',182.6,NULL),(145,'Ruby','Dog','Weimaraner','1996-03-23','Female','AKC','Brown',201.858,NULL),(146,'Kevin','Cat','Siamese','1996-03-24','Male','CFA','White',93.0793,NULL),(147,'Kelly','Dog','Alaskan Malamute','1996-03-25','Male','AKC','White',272.523,NULL),(148,'Wayne','Dog','Akita','1996-02-09','Male','AKC','White',257.898,NULL),(149,'Elizabeth','Cat','Tonkinese','1996-06-06','Female','CFA','Yellow',138.827,NULL),(150,'Brian','Dog','Norfolk Terrier','1996-01-03','Male','AKC','Yellow',168.802,NULL),(151,'Lester','Bird','Parrot','1996-04-09','Male',NULL,'Red',244.198,NULL),(152,'Heather','Dog','Collie','1996-06-03','Female','AKC','Brown/Yellow',218.831,NULL),(153,'Owen','Cat','Manx','1996-06-28','Male',NULL,'Yellow',234.541,NULL),(154,'Orville','Dog','English Bulldog','1996-04-15','Male','AKC','Yellow',197.013,NULL),(155,'Stephen','Cat','Siamese','1996-02-29','Male',NULL,'White',222.935,NULL),(156,'Alvin','Dog','Collie','1996-01-21','Male','AKC','Yellow',280.591,NULL),(157,NULL,'Bird','Parrot','1996-04-12','Male',NULL,'Red',237.819,NULL),(158,'Russell','Dog','Shetland Sheepdog','1996-03-20','Male','AKC','White/Brown',167.166,NULL),(159,NULL,'Reptile','Python','1996-01-13','Male',NULL,'Black',141.98,NULL),(160,'Rufus','Dog','Cocker Spaniel','1996-01-02','Male','AKC','Yellow',176.798,NULL),(161,'Bernard','Dog','Keeshond','1996-05-11','Male',NULL,'Red',244.901,NULL),(162,'Basil','Cat','Japanese Bobtail','1996-05-08','Male','CFA','Red',133.195,NULL),(163,'Bruce','Dog','Jack Russell Terrier','1996-09-20','Male','AKC','Brown/White',261.029,NULL),(164,'Ruby','Bird','Parakeet','1996-01-19','Female',NULL,'Red',253.439,NULL),(165,'Julie','Dog','Dalmation','1996-08-28','Female','AKC','Spotted',172.376,NULL),(166,'Katy','Cat','Japanese Bobtail','1996-03-25','Female','CFA','Yellow',163.726,NULL),(168,'Ray','Cat','Japanese Bobtail','1996-06-04','Male','CFA','Black',225.685,NULL),(169,'Lorene','Dog','Coon Hound','1996-09-11','Female',NULL,'Yellow',132.486,NULL),(170,'Sarah','Dog','Poodle','1996-03-15','Female','AKC','White',250.815,NULL),(171,'Glen','Dog','Chihuahua','1996-04-18','Male','AKC','Yellow',232.264,NULL),(172,'Jackie','Dog','Boston Terrier','1996-08-13','Female','AKC','Gray',242.286,NULL),(173,NULL,'Mammal','Hamster','1996-06-06','Female',NULL,'Yellow',22,NULL),(174,'Lisa','Dog','Doberman','1996-02-24','Female','AKC','Brown',184.957,NULL),(175,'Donald','Cat','Canadian Hairless','1996-07-09','Male','CFA','Brown',193.148,NULL),(176,'DuJuan','Dog','Coon Hound','1996-05-14','Male','AKC','Brown',295.285,NULL),(177,'Jennifer','Dog','Siberian Huskie','1996-04-23','Female','AKC','White',205.758,NULL),(178,'Leisha','Dog','Scottish Terrier','1996-05-26','Female','AKC','Gray',164.633,NULL),(179,'Cassy','Dog','Keeshond','1996-01-05','Female','AKC','Red',139.611,NULL),(180,NULL,'Fish','Angel','1996-04-01','Female',NULL,'Yellow',27,NULL),(181,NULL,'Fish','Tetra','1996-06-16','Male',NULL,'Brown/Red',23,NULL),(182,'Duane','Dog','Rottweiler','1996-05-28','Male','AKC','Red',218.493,NULL),(183,NULL,'Spider','Scorpion','1996-02-02','Male',NULL,'Black',127,NULL),(184,NULL,'Fish','Tetra','1996-05-21','Male',NULL,'Black/Blue',25,NULL),(185,'Ricardo','Dog','Airedale','1996-06-21','Male','AKC','Gray',164.149,NULL),(186,'Francis','Dog','Chinese Shar Pei','1996-01-29','Female','AKC','Brown',240.406,NULL),(187,'Martha','Dog','Poodle','1996-05-15','Female',NULL,'White',133.651,NULL),(188,'Angela','Dog','Chow Chow','1996-06-26','Female','AKC','Yellow',234.977,NULL),(189,'Emma','Bird','Parrot','1996-09-06','Female',NULL,'Yellow',170.082,NULL),(190,'Wendell','Dog','Minature Pinschers','1996-03-30','Male','AKC','Red',163.605,NULL),(191,'Cassy','Dog','Collie','1996-02-13','Female','AKC','Brown/White',233.438,NULL),(192,'Sally','Cat','American Shorthair','1996-06-26','Female','CFA','Black',196.873,NULL),(193,'Kevin','Cat','Sphynx','1996-09-20','Male','CFA','Yellow',240.054,NULL),(195,'Tom','Dog','Dalmation','1996-06-05','Male','AKC','Spotted',195.523,NULL),(196,'Buck','Dog','Doberman','1996-06-05','Male',NULL,'Black',253.544,NULL),(197,NULL,'Spider','Tarantula','1996-06-10','Female',NULL,'Black',221.855,NULL),(198,'Curtis','Dog','Maltese','1996-01-04','Male','AKC','Black',142.678,NULL),(199,'Herbert','Cat','Abyssinian','1996-01-23','Male','CFA','White',163.739,NULL),(200,'Brandy','Dog','Mastiff','1996-08-01','Female','AKC','Brown',120.369,NULL),(201,NULL,'Mammal','Hamster','1996-02-22','Male',NULL,'Brown',22,NULL);
/*!40000 ALTER TABLE `Animal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AnimalOrder`
--

DROP TABLE IF EXISTS `AnimalOrder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AnimalOrder` (
  `OrderID` int NOT NULL AUTO_INCREMENT,
  `OrderDate` date DEFAULT NULL,
  `ReceiveDate` date DEFAULT NULL,
  `SupplierID` int NOT NULL,
  `ShippingCost` float DEFAULT NULL,
  `EmployeeID` int NOT NULL,
  PRIMARY KEY (`OrderID`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AnimalOrder`
--

LOCK TABLES `AnimalOrder` WRITE;
/*!40000 ALTER TABLE `AnimalOrder` DISABLE KEYS */;
INSERT INTO `AnimalOrder` VALUES (1,'1996-06-11','1996-06-23',4,32.0626,5),(2,'1996-01-30','1996-08-31',2,50.808,1),(3,'1996-03-07','1996-08-01',4,42.3681,3),(4,'1996-03-06','1996-10-26',9,62.2025,8),(5,'1996-04-21','1996-04-28',5,68.2357,3),(6,'1996-01-11','1996-05-03',7,43.2731,4),(7,'1996-04-06','1996-05-01',8,51.0253,9),(8,'1996-02-22','1996-03-17',7,62.8987,4),(9,'1996-01-15','1996-03-14',3,46.1884,9),(10,'1996-02-17','1996-09-24',8,63.2598,7),(11,'1996-02-25','1996-07-16',2,47.4512,7),(12,'1996-01-13','1996-02-18',5,52.3566,6),(13,'1996-02-27','1996-03-17',9,45.7489,5),(14,'1996-04-03','1996-08-16',3,52.0724,9),(15,'1996-01-30','1996-06-19',7,61.4642,5),(16,'1996-02-02','1996-02-26',6,51.546,5),(17,'1996-04-08','1996-10-19',2,52.6153,2),(18,'1996-05-19','1996-09-05',5,40.6514,5),(19,'1996-03-19','1996-03-24',9,49.1957,3),(20,'1996-02-17','1996-02-25',9,55.2797,7),(21,'1996-01-29','1996-10-03',4,49.0741,2),(22,'1996-04-10','1996-07-03',2,48.0689,3),(23,'1996-01-20','1996-03-04',7,45.1382,5),(24,'1996-04-22','1996-07-16',6,60.2022,5),(25,'1996-01-04','1996-06-30',7,40.5747,3),(26,'1996-05-10','1996-05-11',1,40.3457,6),(27,'1996-01-10','1996-07-27',4,53.2154,5),(28,'1996-01-22','1996-02-20',3,54.0879,3),(29,'1996-01-27','1996-10-30',9,50.4881,3),(30,'1996-01-28','1996-04-22',3,61.0156,3),(31,'1996-02-18','1996-07-04',2,47.6748,9),(32,'1996-02-08','1996-07-24',3,57.4353,8),(33,'1996-01-23','1996-04-29',6,53.7268,2),(34,'1996-05-02','1996-05-08',7,46.3461,9),(35,'1996-03-01','1996-07-11',5,47.467,5),(36,'1996-01-21','1996-03-16',8,40.7272,7),(37,'1996-07-03','1996-09-18',3,38.642,4),(38,'1996-02-16','1996-02-20',3,62.0992,8),(39,'1996-02-05','1996-03-31',4,44.3324,8),(40,'1996-02-15','1996-06-16',7,61.2229,5),(41,'1996-02-10','1996-05-02',7,44.9812,4),(42,'1996-02-14','1996-04-16',8,46.394,3),(43,'1996-02-23','1996-08-22',4,39.3413,6),(44,'1996-02-21','1996-05-13',6,56.2203,1),(45,'1996-03-14','1996-04-28',9,61.0603,4),(46,'1996-02-10','1996-10-06',7,60.8725,1),(47,'1996-01-04','1996-06-28',6,39.6836,2),(48,'1996-04-09','1996-09-10',1,54.8572,3),(49,'1996-07-11','1996-07-15',5,53.8839,2),(50,'1996-02-12','1996-02-17',3,48.161,3);
/*!40000 ALTER TABLE `AnimalOrder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AnimalOrderItem`
--

DROP TABLE IF EXISTS `AnimalOrderItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AnimalOrderItem` (
  `OrderId` int NOT NULL,
  `AnimalID` int NOT NULL,
  `Cost` float DEFAULT NULL,
  PRIMARY KEY (`OrderId`,`AnimalID`),
  KEY `fk_animalorderitem_animal` (`AnimalID`),
  CONSTRAINT `fk_animalorderitem_animal` FOREIGN KEY (`AnimalID`) REFERENCES `Animal` (`AnimalID`),
  CONSTRAINT `fk_animalorderitem_order` FOREIGN KEY (`OrderId`) REFERENCES `AnimalOrder` (`OrderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AnimalOrderItem`
--

LOCK TABLES `AnimalOrderItem` WRITE;
/*!40000 ALTER TABLE `AnimalOrderItem` DISABLE KEYS */;
INSERT INTO `AnimalOrderItem` VALUES (1,59,131.46),(2,27,34.25),(2,30,74.65),(2,79,153.74),(2,126,51.01),(2,171,127.91),(2,200,89.52),(3,69,60.83),(3,125,77.04),(3,173,9.73),(3,192,63),(4,47,95.99),(4,120,202.62),(4,132,73.07),(4,152,143.29),(4,154,119.9),(4,163,216.65),(5,68,66.28),(6,16,123.84),(6,58,110.2),(6,67,139.09),(6,104,69.56),(6,146,35.81),(7,10,138.44),(7,145,67.18),(8,25,14.28),(8,32,54.98),(8,87,168.58),(8,148,154.74),(9,5,8.09),(9,39,104.9),(9,78,120.08),(9,116,36.76),(9,183,59.17),(10,108,109.4),(10,136,119.43),(11,44,73.01),(11,65,106.84),(11,196,129.13),(12,41,68.75),(12,94,142.25),(12,134,32.37),(12,164,124.84),(13,9,0.89),(13,37,50.58),(13,122,199.83),(14,2,3.93),(14,26,89.35),(15,57,32.89),(15,95,91.76),(15,176,200.53),(16,7,103.65),(16,106,83.74),(16,199,30.59),(17,15,173.56),(17,109,91.04),(17,131,76.28),(17,169,80.27),(18,99,92.08),(18,100,72.44),(19,149,52.91),(19,158,90.27),(19,201,5.79),(20,8,98.21),(20,71,84.11),(20,73,30.15),(20,159,67.54),(21,33,49.33),(21,74,67.6),(21,91,78.04),(21,96,158.03),(21,112,47.47),(21,119,155.81),(21,135,41.45),(21,141,105.91),(21,165,120.73),(22,35,158.42),(22,124,113.35),(22,184,16.5),(23,45,94),(23,85,104.94),(23,150,61.9),(23,175,108.39),(23,186,227.47),(24,18,54.63),(24,61,114.79),(24,62,65.72),(24,139,78.03),(24,181,18.3),(25,49,122.14),(25,52,88.53),(25,55,149.28),(25,66,156.34),(25,77,128.39),(25,81,147.99),(25,138,163.1),(26,64,122.72),(27,177,125.24),(27,179,28.84),(27,185,57.39),(28,84,120.77),(28,97,69.13),(29,46,130.89),(29,75,68.87),(29,155,129.41),(29,166,48.66),(29,193,141.03),(30,21,32.96),(30,80,62.41),(30,88,47.38),(30,92,72.19),(30,127,71.91),(30,170,202.76),(31,98,111.02),(31,142,47.26),(31,156,109.8),(31,168,132.95),(32,11,84.3),(32,50,197.03),(32,53,10.59),(33,42,59.06),(33,89,79.23),(33,172,150.12),(33,178,128.79),(33,190,146.84),(34,29,13.4),(34,130,84.54),(34,151,53.36),(35,17,65.77),(35,56,102.53),(35,83,94.9),(35,90,60.29),(35,182,52.81),(35,195,142.13),(36,20,90.8),(36,63,124.29),(36,70,165.04),(36,93,91.3),(36,153,115.98),(36,162,64.76),(36,188,75.4),(36,191,102.15),(37,110,54.63),(37,117,3.32),(38,4,88.76),(38,43,129.52),(38,115,98.44),(39,40,94.78),(39,51,42.64),(39,113,73.86),(39,128,62.2),(39,174,33.74),(40,28,32.47),(40,129,14.12),(40,140,82.57),(40,161,70.21),(41,24,43.83),(41,36,108.83),(41,54,60.72),(41,180,15.37),(42,14,60.62),(42,48,117.98),(42,111,148),(43,31,114.71),(43,133,211.22),(44,23,79.51),(44,60,130.28),(44,102,84.95),(44,105,43.4),(44,137,97.84),(44,143,87.18),(44,144,89.77),(44,157,199.82),(45,147,271.11),(46,19,91.26),(46,72,7.74),(46,86,18.58),(46,103,168.91),(46,118,2.84),(46,189,59.89),(47,13,2.75),(47,101,200.48),(47,160,138.2),(47,187,56.15),(48,121,148.11),(49,34,75.97),(49,197,94.87),(50,6,36.09),(50,12,106.67),(50,82,69.13),(50,198,113.01);
/*!40000 ALTER TABLE `AnimalOrderItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Customer`
--

DROP TABLE IF EXISTS `Customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Customer` (
  `CustomerID` int NOT NULL,
  `Phone` bigint DEFAULT NULL,
  `FirstName` varchar(255) DEFAULT NULL,
  `LastName` varchar(255) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `ZipCode` varchar(50) DEFAULT NULL,
  `CityID` float DEFAULT NULL,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `CustomerID_UNIQUE` (`CustomerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Customer`
--

LOCK TABLES `Customer` WRITE;
/*!40000 ALTER TABLE `Customer` DISABLE KEYS */;
INSERT INTO `Customer` VALUES (1,NULL,'Walkin','Walkin',NULL,NULL,NULL),(2,8088019830,'Brent','Cummings','9197 Hatchet Drive','96815.0',7347),(3,6178436488,'Dwight','Logan','1760 Clearview Street','2109.0',8133),(4,5020070907,'Shatika','Gilbert','4407 Green Street','40342.0',7963),(5,7013845623,'Charlotte','Anderson','4333 Highland Court','58102.0',9175),(6,6067403304,'Searoba','Hopkins','3183 Highland Court','40330.0',7955),(7,4081049807,'Anita','Robinson','8177 Horse Park Lane','95035.0',6366),(8,6066888141,'Cora','Reid','8351 Locust Street','41073.0',7925),(9,7025333419,'Elwood','Henson','4042 West Ridge Drive','89125.0',9636),(10,3027017398,'Kaye','Maynard','5095 Sugar Grove Road','19901.0',6829),(11,5024441587,'Lee','Gentry','5744 High Street','40342.0',7963),(12,6080062256,'Chris','Haley','6087 Spring Street','54601.0',11596),(13,3044776629,'Jolene','Garrison','1830 Glenview Drive','26261.0',26261),(14,6147212870,'Beth','Patterson','3688 Long Hollow Pike','43216.0',10031),(15,4091163589,'Connie','Fisher','4491 Evergreen Circle','77550.0',11013),(16,5028633037,'Rosie','Wade','7661 Trident Place','42240.0',7960),(17,6066347076,'Carly','Embry','2869 Sanders Ferry Road','40383.0',8000),(18,4143315679,'David','Lawrence','9326 Halltown Road','53916.0',11559),(19,5058254065,'Lisa','Zhang','477 Rolling Acres Drive','87501.0',9616),(20,2075857583,'Reva','Kidd','5594 Halltown Road','4401.0',8525),(21,6156469874,'Sarah','Mills','8904 Horseshoe Road','37167.0',10893),(22,7069774337,'Inga','Adkins','8831 Cardinal Lane','30720.0',7244),(23,6060846983,'Jerome','Cummings','2501 Club Circle','41101.0',8002),(24,6159698901,'Floyd','Young','969 Windsor Park Lane','37130.0',10877),(25,5028638898,'Gaylen','Wilson','1221 Johnson Drive','40229.0',7959),(26,6066919745,'Trina','Howe','2821 35th Street','40701.0',7932),(27,6088253740,'Archie','Mitchell','1326 Cedar Avenue','53590.0',11647),(28,4085757000,'Gina','Samuels','2026 Homewood Blvd.','95035.0',6366),(29,8020784749,'Kelly','Moore','6116 Clearview Street','5753.0',11393),(30,9190147544,'Rodney','Edmunds','5560 Greenfield Lane','27514.0',9090),(31,3132607386,'Nancy','Froedge','1399 Williamsburg Drive','48152.0',48152),(32,6192812485,'Searoba','Allbright','5260 College Street','92109.0',92109),(33,3145105228,'Colleen','Latham','2864 Dolhous Drive','63366.0',8962),(34,4141682952,'Cathy','Hampton','7295 Hazel Path','53203.0',53203),(35,4069879338,'Kimberly','Adams','5064 Pleasant Valley Drive','59901.0',9069),(36,5010712650,'Lloyd','Holland','9011 Bayshore Drive','72201.0',5984),(37,5170398300,'Fannie','McPherson','4184 Tick Ridge Road','48924.0',48924),(38,3074778112,'Jay','Franklin','9105 Ridge Top Road','82070.0',82070),(39,3043528602,'Dorothy','Williams','9286 Jackson Avenue','25550.0',25550),(40,6120890074,'Darrell','Oberhausen','9556 Cumberland Hills Drive','55101.0',8864),(41,5026917565,'Melinda','Craig','9217 Blue Lake Way','40218.0',7979),(42,6150411934,'Jackie','Froedge','243 West Ridge Drive','37027.0',10819),(43,5170573491,'Jasper','Killen','4869 Sequoia Lane','48924.0',48924),(44,4129333710,'Dale','Hammer','9707 Elm Hill Circle','15106.0',10617),(45,6063720229,'Shirley','Gregory','8982 Circle Drive','40356.0',7981),(46,2016542441,'Cassy','Tuck','7977 Fairways Drive','7015.0',9322),(47,8040205842,'Bernice','Carver','8170 Roberts Road','23320.0',11276),(48,5082012483,'Dwight','Parrish','9904 Plum Springs Road','1613.0',8363),(49,2108517566,'Wallace','Galloway','4358 Cherokee Road','78284.0',78284),(50,7049791610,'Lenny','Lee','9940 Girkin Road','28204.0',9091),(51,7154837737,'Clyde','Jernigan','5325 Cherry Street','54022.0',11636),(52,9192891383,'Gladys','Pendleton','1805 Sunset Place','27611.0',9144),(53,8179987894,'Jackie','Grayson','6540 Sandy Valley Road','76010.0',76010),(54,5029712715,'Mark','Green','8968 Camino Circle','40291.0',7943),(55,6063750721,'Wendell','Thomas','8963 Pine Branch Trace','40330.0',7955),(56,5074370059,'Sidney','Hughes','4386 Sherwood Lane','56007.0',8771),(57,2192632040,'Phillip','Carter','20 Green Street','27514.0',9090),(58,7035430176,'Sam','McCain','445 Industrial Drive','22101.0',11368),(59,3609177520,'Cindy','Rhodes','7425 Virginia Street','98501.0',11533),(60,5020293909,'Lou Ann','Baez','2528 Hot Springs Avenue','42718.0',7930),(61,3044161309,'Marvin','McDonald','8045 Lovers Lane','25301.0',11671),(62,9134961897,'Phyllis','Tuck','2150 Volunteer Drive','66502.0',7901),(63,3014856261,'Earl','Grimes','6485 Pepper Street','21701.0',8426),(64,5021357679,'Conrad','Fowler','8114 Cherry Street','42420.0',7957),(65,8175287955,'Linda','Riggs','593 White Oak Court','76101.0',76101),(66,6066834113,'Adrian','Greer','6597 Skyline Drive','40422.0',7935),(67,4147157191,'Floyd','Kennedy','3566 Elementary Drive','54305.0',54305),(68,9013121555,'Ella','Kidd','3626 Webster Road','38101.0',10871),(69,9192058147,'Mandy','Elliott','4839 Long Hollow Pike','27514.0',9090),(70,3134388733,'Jerry','Poe','5879 Dixie Way','48233.0',48233),(71,7028671893,'Herman','Moseley','4461 Southern Drive','89114.0',89114),(72,3606499754,'Gayle','Bailey','8233 Whisper Woods Road','98201.0',11522),(73,6030757437,'Hershelly','Keen','8124 Industrial Drive','3065.0',9261),(74,6062413182,'Frieda','Hinton','9015 Cherokee Road','41042.0',7945),(75,8024335007,'Sharon','Sexton','2551 Elementary Drive','5641.0',11382),(76,3040621621,'Evelyn','Montgomery','6386 Main Street','25550.0',25550),(77,5205208733,'Bart','Gillespie','3422 Breeder Road','85726.0',6067),(78,5056462748,'Elaine','Hall','1790 Cedar Avenue','88310.0',9586),(79,3179958449,'Darcy','Pitts','523 Shoreside Drive','46206.0',7808),(80,5158465222,'Juanita','Vance','8916 High Street','50010.0',7378),(81,6066612765,'Bessie','Baez','7313 Humble Avenue','41031.0',7934),(82,3074485424,'Basil','Davidson','9102 Rocky Mound Road','82071.0',11701),(83,4143348130,'Ada','Jones','4590 Tobacco Road','54956.0',11614),(84,6174673438,'David','Sims','6623 Glenview Drive','2116.0',2116),(85,5025951052,'Glen','Cartwright','448 Sanders Ferry Road','42001.0',42001),(86,6181099250,'Bert','Hooks','9801 Hatchet Drive','62269.0',7661),(87,6067933172,'Lucille','Cummings','1209 Center Point Road','40391.0',8004),(88,7036112557,'Christa','Mallory','6370 N. Main Street','22101.0',11368),(89,3088731375,'Kaye','Foster','520 Pace Street','68847.0',9198),(90,4084272162,'Connie','Nichols','4039 Greensburg Street','95035.0',6366),(91,5028827631,'Conrad','Ferguson','6300 Dorcas Drive','40207.0',40207),(92,6084813369,'Tim','Zeller','9682 Ridge Top Road','53701.0',53701),(93,3142425743,'Floyd','Dye','7730 Crosby Drive','63155.0',63155),(94,8024584329,'Dan','Kennedy','3108 Troon Court','5401.0',11402),(95,7151404710,'Clarence','Kinser','6964 Poplar Ridge Road','54701.0',54701),(96,9133444761,'Inez','Maynard','3832 College Street','66502.0',7901),(97,7156230861,'Adam','Rich','2759 Peterson Avenue','54401.0',54401),(98,7016913002,'Wilmer','Pardue','1790 N. Main Street','58102.0',9175),(99,7172443484,'Luther','Bell','5703 Cave Springs Road','17325.0',10493),(100,6063269807,'Reva','Killen','5442 Halltown Road','41056.0',7971);
/*!40000 ALTER TABLE `Customer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Employee`
--

DROP TABLE IF EXISTS `Employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Employee` (
  `EmployeeID` int NOT NULL AUTO_INCREMENT,
  `LastName` varchar(50) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `ZipCode` varchar(20) DEFAULT NULL,
  `CityID` int DEFAULT NULL,
  `TaxPayerID` varchar(50) DEFAULT NULL,
  `DateHired` date DEFAULT NULL,
  PRIMARY KEY (`EmployeeID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Employee`
--

LOCK TABLES `Employee` WRITE;
/*!40000 ALTER TABLE `Employee` DISABLE KEYS */;
INSERT INTO `Employee` VALUES (1,'Reeves','Keith','4021467714','4714 Countrywood Drive','68501',9201,'387-39-3938','1996-01-29'),(2,'Gibson','Bill','9192450526','7140 Blackjack Road','27842',9146,'151-23-1243','1996-03-31'),(3,'Reasoner','Katy','4134148275','7660 Trail East Drive','1101',8313,'623-46-2346','1996-02-17'),(4,'Hopkins','Alan','4125249814','3331 Union Road','15219',10641,'234-62-3463','1996-02-08'),(5,'James','Leisha','4070266653','6363 Springhill Avenue','32802',7083,'472-45-6456','1996-01-06'),(6,'Eaton','Anissa','9064467957','9127 Jackson Avenue','49930',8676,'245-72-4564','1996-08-23'),(7,'Farris','Dustin','6158915545','9145 Liberty Street','37202',10878,'946-54-5635','1996-03-28'),(8,'Carpenter','Carlos','2155458897','4098 Locust Street','19104',10592,'247-24-5765','1996-12-29'),(9,'O\'Connor','Jessica','2031700146','6824 Longview Drive','6101',6731,'834-57-3457','1996-07-23'),(10,'Shields','Howard','3046079081','315 Horseshoe Road','26003',11692,'245-74-5776','1996-07-13');
/*!40000 ALTER TABLE `Employee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Merchandise`
--

DROP TABLE IF EXISTS `Merchandise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Merchandise` (
  `ItemID` int NOT NULL AUTO_INCREMENT,
  `Description` varchar(255) DEFAULT NULL,
  `QuantityOnHand` int NOT NULL DEFAULT '0',
  `ListPrice` float NOT NULL,
  `Category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ItemID`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Merchandise`
--

LOCK TABLES `Merchandise` WRITE;
/*!40000 ALTER TABLE `Merchandise` DISABLE KEYS */;
INSERT INTO `Merchandise` VALUES (1,'Dog Kennel-Small',14,45,'Dog'),(2,'Dog Kennel-Medium',14,65,'Dog'),(3,'Dog Kennel-Large',10,85,'Dog'),(4,'Dog Kennel-Extra Large',6,110,'Dog'),(5,'Cat Bed-Small',36,25,'Cat'),(6,'Cat Bed-Medium',35,35,'Cat'),(7,'Dog Toy',134,4,'Dog'),(8,'Cat Toy',182,3,'Cat'),(9,'Dog Food-Dry-10 pound',211,7.5,'Dog'),(10,'Dog Food-Dry-25 pound',100,16,'Dog'),(11,'Dog Food-Dry-50 pound',373,30,'Dog'),(12,'Cat Food-Dry-5 pound',413,7,'Cat'),(13,'Cat Food-Dry-10 pound',224,10,'Cat'),(14,'Cat Food-Dry-25 pound',300,18,'Cat'),(15,'Dog Food-Can-Regular',452,0.4,'Dog'),(16,'Dog Food-Can-Premium',1179,0.8,'Dog'),(17,'Cat Food-Can-Regular',7470,0.5,'Cat'),(18,'Cat Food-Can-Premium',1874,1,'Cat'),(19,'Cat Litter-10 pound',128,8,'Cat'),(20,'Wood Shavings/Bedding',141,10,'Mammal'),(21,'Bird Cage-Medium',5,25,'Bird'),(22,'Bird Cage-Large',15,75,'Bird'),(23,'Aquarium-25 gal',28,75,'Fish'),(24,'Aquarium-100 gal',11,150,'Fish'),(25,'Fish Food',113,3,'Fish'),(26,'Aquarium Decoration',48,6,'Fish'),(27,'Aquarium Filter & Pump',18,35,'Fish'),(28,'Aquarium Light',65,25,'Fish'),(29,'Flea Collar-Cat',240,6,'Cat'),(30,'Flea Collar-Dog-Medium',429,7,'Dog'),(31,'Flea Collar-Dog-Large',1018,8.5,'Dog'),(32,'Collar-Dog-Small',47,12,'Dog'),(33,'Collar-Dog-Medium',92,15,'Dog'),(34,'Collar-Dog-Large',132,20,'Dog'),(35,'Collar-Cat',150,8,'Cat'),(36,'Leash',172,22,'Dog'),(37,'Brush-Stiff',34,8,'Dog'),(38,'Brush-Soft',50,8,'Cat'),(39,'Litter Box',131,8,'Cat'),(40,'Litter Box-Covered',20,15,'Cat');
/*!40000 ALTER TABLE `Merchandise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MerchandiseOrder`
--

DROP TABLE IF EXISTS `MerchandiseOrder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MerchandiseOrder` (
  `PONumber` int NOT NULL AUTO_INCREMENT,
  `OrderDate` date DEFAULT NULL,
  `ReceiveDate` date DEFAULT NULL,
  `SupplierID` int DEFAULT NULL,
  `EmployeeID` int DEFAULT NULL,
  `ShippingCost` float DEFAULT '0',
  PRIMARY KEY (`PONumber`),
  KEY `fk_merchorder_employee` (`EmployeeID`),
  CONSTRAINT `fk_merchorder_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `Employee` (`EmployeeID`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MerchandiseOrder`
--

LOCK TABLES `MerchandiseOrder` WRITE;
/*!40000 ALTER TABLE `MerchandiseOrder` DISABLE KEYS */;
INSERT INTO `MerchandiseOrder` VALUES (1,'1996-03-06','1996-03-08',10,4,33.5449),(2,'1996-05-28','1996-06-04',11,3,28.6399),(3,'1996-08-19','1996-08-22',15,4,39.0153),(4,'1996-04-18','1996-04-30',14,5,42.0751),(5,'1996-08-18','1996-08-25',10,8,32.3296),(6,'1996-04-22','1996-04-29',13,9,21.8741),(7,'1996-06-07','1996-06-18',14,7,14.2196),(8,'1996-07-01','1996-07-03',11,1,39.2962),(9,'1996-04-27','1996-05-03',11,3,33.892),(10,'1996-05-04','1996-05-08',13,3,31.0438),(11,'1996-06-18','1996-06-22',11,5,25.3818),(12,'1996-10-06','1996-10-07',13,8,33.7617),(13,'1996-10-06','1996-10-09',12,7,40.3896),(14,'1996-05-20','1996-05-22',12,5,29.2682),(15,'1996-09-30','1996-10-04',10,9,37.9814),(16,'1996-02-27','1996-03-04',12,5,32.4214),(17,'1996-08-09','1996-08-15',14,7,42.2475),(18,'1996-03-27','1996-04-04',14,5,60.1296),(19,'1996-02-04','1996-02-06',14,2,29.9436),(20,'1996-08-11','1996-08-16',13,8,21.6762),(21,'1996-06-29','1996-06-30',15,8,46.9106),(22,'1996-02-25','1996-02-29',12,5,32.553),(23,'1996-09-19','1996-09-22',13,3,43.6357),(24,'1996-10-27','1996-10-29',11,9,18.6879),(25,'1996-08-31','1996-09-15',11,6,28.3478);
/*!40000 ALTER TABLE `MerchandiseOrder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OldAnimal`
--

DROP TABLE IF EXISTS `OldAnimal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OldAnimal` (
  `AnimalID` int DEFAULT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `Category` varchar(255) DEFAULT NULL,
  `Breed` varchar(255) DEFAULT NULL,
  `DateBorn` varchar(255) DEFAULT NULL,
  `Gender` varchar(255) DEFAULT NULL,
  `Registered` varchar(255) DEFAULT NULL,
  `Color` varchar(255) DEFAULT NULL,
  `ListPrice` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OldAnimal`
--

LOCK TABLES `OldAnimal` WRITE;
/*!40000 ALTER TABLE `OldAnimal` DISABLE KEYS */;
INSERT INTO `OldAnimal` VALUES (13,NULL,'Fish','Tetra','1996-03-01 00:00:00','Male',NULL,'Red',12),(49,'Martha','Cat','Korat','1996-03-16 00:00:00','Female',NULL,'Gray/Blue',218.958),(52,'Buck','Dog','St Bernard','1996-01-01 00:00:00','Male','AKC','Brown/White',277.732),(55,'Alice','Dog','Cairn Terrier','1996-01-03 00:00:00','Female','AKC','Gray/Blue',245.665),(66,'Seth','Cat','Russian Blue','1996-01-09 00:00:00','Male','CFA','Gray/Blue',275.106),(77,'Damon','Cat','Bombay','1996-01-17 00:00:00','Male','CFA','Black',178.165),(81,NULL,'Bird','Canary','1996-07-29 00:00:00','Female',NULL,'Yellow',229.442),(101,'Bessie','Dog','Australian Shepherd','1996-01-03 00:00:00','Female','AKC','White/Brown',259.32),(138,'Owen','Cat','Japanese Bobtail','1996-05-21 00:00:00','Male','CFA','White',232.468),(160,'Rufus','Dog','Cocker Spaniel','1996-01-02 00:00:00','Male','AKC','Yellow',212.158),(187,'Martha','Dog','Poodle','1996-05-15 00:00:00','Female',NULL,'White',160.381);
/*!40000 ALTER TABLE `OldAnimal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OrderItem`
--

DROP TABLE IF EXISTS `OrderItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OrderItem` (
  `PONumber` int NOT NULL,
  `ItemID` int NOT NULL,
  `Quantity` float DEFAULT NULL,
  `Cost` float DEFAULT '0',
  PRIMARY KEY (`PONumber`,`ItemID`),
  CONSTRAINT `fk_orderitem_merchorder` FOREIGN KEY (`PONumber`) REFERENCES `MerchandiseOrder` (`PONumber`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OrderItem`
--

LOCK TABLES `OrderItem` WRITE;
/*!40000 ALTER TABLE `OrderItem` DISABLE KEYS */;
INSERT INTO `OrderItem` VALUES (1,27,8,24.65),(1,30,208,4.42),(2,4,6,85.67),(2,17,1546,0.36),(2,18,321,0.8),(2,19,128,6.45),(3,3,5,67.84),(3,32,47,8.86),(3,36,42,21.22),(4,16,179,0.6),(4,28,14,17.87),(4,36,34,20.28),(5,6,12,25.76),(5,37,16,7.07),(6,9,55,5.21),(6,11,103,23.78),(6,17,1482,0.41),(6,21,5,17.64),(6,23,9,41.5),(6,24,3,124.2),(6,37,18,5.17),(7,20,66,7.99),(7,24,4,93.15),(8,16,233,0.47),(8,18,292,0.78),(8,30,221,6.09),(8,38,13,6.01),(9,13,74,6.16),(9,28,12,13.74),(10,5,19,17.45),(10,16,205,0.66),(10,18,312,0.81),(10,31,328,7.83),(11,17,1424,0.39),(11,28,14,18.92),(11,33,92,12.09),(12,2,7,45.18),(12,12,94,5.14),(12,16,185,0.59),(12,23,9,42.4),(12,28,13,22.07),(13,11,88,21.98),(13,12,97,6.53),(13,13,79,7.39),(13,15,452,0.27),(13,18,332,0.69),(13,29,115,4.8),(13,38,13,7.26),(14,2,7,50.63),(14,9,48,6.81),(14,13,71,8.13),(14,17,1307,0.39),(14,29,125,4.62),(14,39,25,6.82),(15,6,12,31.01),(15,7,134,3.08),(15,38,11,5.37),(16,8,85,2.33),(16,12,105,6.04),(16,16,178,0.55),(16,25,39,2.18),(16,26,48,4.57),(16,39,24,6.55),(17,5,17,19.01),(17,11,94,21.41),(17,18,301,0.77),(17,23,10,35.75),(17,24,4,134.73),(17,27,10,25.13),(17,31,359,6.17),(18,25,39,2.84),(19,12,117,5.46),(19,31,331,5.68),(19,36,48,17.97),(20,9,57,5.52),(20,11,88,21.3),(21,18,316,0.78),(21,34,132,16.14),(21,38,13,4.46),(21,39,27,5.27),(22,1,14,30.94),(22,9,51,6.69),(22,28,12,14.46),(22,36,48,14.92),(22,39,25,5.37),(23,3,5,65.13),(23,17,1711,0.42),(23,39,30,4.69),(24,6,11,22.67),(24,8,97,2.27),(24,20,75,8.56),(24,40,20,13.44),(25,16,199,0.64),(25,25,35,1.99);
/*!40000 ALTER TABLE `OrderItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Sale`
--

DROP TABLE IF EXISTS `Sale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Sale` (
  `SaleID` int NOT NULL,
  `SaleDate` date DEFAULT NULL,
  `EmployeeID` int DEFAULT NULL,
  `CustomerID` int DEFAULT NULL,
  PRIMARY KEY (`SaleID`),
  UNIQUE KEY `SaleID_UNIQUE` (`SaleID`),
  KEY `fk_sale_employee` (`EmployeeID`),
  KEY `fk_sale_customer` (`CustomerID`),
  CONSTRAINT `fk_sale_customer` FOREIGN KEY (`CustomerID`) REFERENCES `Customer` (`CustomerID`) ON DELETE SET NULL,
  CONSTRAINT `fk_sale_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `Employee` (`EmployeeID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Sale`
--

LOCK TABLES `Sale` WRITE;
/*!40000 ALTER TABLE `Sale` DISABLE KEYS */;
INSERT INTO `Sale` VALUES (4,'1996-08-14',3,18),(5,'1996-10-31',2,21),(6,'1996-09-15',5,44),(7,'1996-02-10',4,42),(8,'1996-03-10',1,15),(9,'1996-02-10',3,16),(10,'1996-11-01',8,53),(11,'1996-12-24',8,60),(12,'1996-08-15',2,53),(13,'1996-01-30',7,49),(14,'1996-09-18',2,9),(15,'1996-07-20',9,39),(16,'1996-09-18',8,62),(17,'1996-02-12',4,71),(18,'1996-12-21',5,35),(19,'1996-02-14',7,14),(20,'1996-03-09',5,2),(21,'1996-04-23',2,61),(22,'1996-11-27',2,16),(23,'1996-07-08',6,27),(24,'1996-08-24',2,99),(25,'1996-02-17',6,85),(26,'1996-01-11',5,49),(27,'1996-10-25',2,47),(28,'1996-02-12',6,24),(29,'1996-06-16',7,82),(30,'1996-10-15',4,26),(31,'1996-04-22',7,19),(32,'1996-03-09',3,72),(33,'1996-03-15',1,78),(34,'1996-04-06',8,35),(35,'1996-06-02',9,38),(36,'1996-03-18',1,88),(37,'1996-03-17',6,21),(38,'1996-02-10',1,94),(39,'1996-10-19',4,51),(40,'1996-04-07',5,9),(41,'1996-06-09',3,38),(42,'1996-03-21',3,3),(43,'1996-04-11',8,72),(44,'1996-02-17',2,22),(45,'1996-03-13',3,28),(46,'1996-06-02',5,23),(47,'1996-09-21',8,38),(48,'1996-02-16',9,69),(49,'1996-09-16',8,66),(50,'1996-09-28',9,82),(51,'1996-06-19',6,80),(52,'1996-06-18',9,57),(53,'1996-02-05',5,43),(54,'1996-11-01',3,8),(55,'1996-01-15',5,22),(56,'1996-12-22',6,99),(57,'1996-02-16',5,25),(58,'1996-10-24',7,74),(59,'1996-08-23',3,1),(60,'1996-03-09',7,86),(61,'1996-02-08',8,43),(62,'1996-02-08',4,91),(63,'1996-12-09',4,20),(64,'1996-02-18',8,74),(65,'1996-02-13',4,95),(66,'1996-03-21',6,99),(67,'1996-03-09',7,19),(68,'1996-01-11',2,8),(69,'1996-03-04',9,94),(70,'1996-04-13',8,11),(71,'1996-07-26',8,75),(72,'1996-11-04',7,55),(73,'1996-08-15',6,20),(74,'1996-04-17',5,50),(75,'1996-06-21',2,24),(76,'1996-02-18',3,79),(77,'1996-11-08',1,7),(78,'1996-08-08',3,48),(79,'1996-01-04',2,9),(80,'1996-11-05',1,32),(81,'1996-06-06',7,55),(82,'1996-05-05',5,36),(83,'1996-11-16',6,56),(84,'1996-10-21',7,66),(85,'1996-03-28',2,71),(86,'1996-03-28',2,81),(87,'1996-02-17',4,68),(88,'1996-01-28',2,81),(89,'1996-03-11',4,23),(90,'1996-01-28',6,40),(91,'1996-02-16',5,56),(92,'1996-09-20',4,90),(93,'1996-02-12',2,25),(94,'1996-01-13',7,35),(95,'1996-05-19',7,78),(96,'1996-10-24',1,12),(97,'1996-06-01',2,56),(98,'1996-08-13',8,63),(99,'1996-01-11',8,19),(100,'1996-09-13',4,84),(101,'1996-08-09',9,90),(102,'1996-05-31',9,26),(103,'1996-12-03',7,86),(104,'1996-02-13',4,13),(105,'1996-02-14',5,57),(106,'1996-02-17',2,79),(107,'1996-02-10',4,38),(108,'1996-07-15',5,34),(109,'1996-01-29',3,30),(110,'1996-03-05',6,19),(111,'1996-02-10',2,23),(112,'1996-02-19',5,9),(113,'1996-02-16',4,37),(114,'1996-10-08',8,80),(115,'1996-06-04',3,60),(116,'1996-09-20',7,38),(117,'1996-08-20',9,95),(118,'1996-01-30',2,72),(119,'1996-06-02',3,39),(120,'1996-06-20',3,6),(121,'1996-06-06',4,73),(122,'1996-10-11',8,29),(123,'1996-04-05',3,35),(124,'1996-02-17',6,48),(125,'1996-06-11',8,76),(126,'1996-11-09',8,90),(127,'1996-10-17',7,65),(128,'1996-04-09',4,43),(129,'1996-09-24',10,58),(130,'1996-03-06',3,9),(131,'1996-04-23',9,59),(132,'1996-02-17',8,28),(133,'1996-03-19',4,54),(134,'1996-02-18',7,75),(135,'1996-10-11',2,58),(136,'1996-02-21',9,42),(137,'1996-10-26',3,46),(138,'1996-08-16',2,74),(139,'1996-03-14',9,36),(140,'1996-03-14',7,95),(141,'1996-03-19',5,46),(142,'1996-01-30',7,69),(143,'1996-02-17',8,51),(144,'1996-01-27',8,59),(145,'1996-03-25',2,8),(146,'1996-03-19',7,98),(147,'1996-03-19',2,18),(148,'1996-07-29',9,80),(149,'1996-05-30',6,60),(150,'1996-05-09',3,14),(151,'1996-07-30',3,5),(152,'1996-06-02',5,30),(153,'1996-02-18',10,75),(154,'1996-11-05',1,27),(155,'1996-03-19',4,58),(156,'1996-02-29',4,66),(157,'1996-03-19',4,44),(158,'1996-03-24',8,94),(159,'1996-09-17',4,53),(160,'1996-08-24',3,27),(161,'1996-06-13',2,96),(162,'1996-03-10',5,22),(163,'1996-09-30',5,63),(164,'1996-05-30',5,47),(165,'1996-03-16',3,33),(166,'1996-02-16',4,92),(167,'1996-03-10',3,60),(168,'1996-01-23',7,3),(169,'1996-02-25',4,84),(170,'1996-02-27',9,21),(171,'1996-03-12',7,40),(172,'1996-03-09',10,73),(173,'1996-02-25',7,2),(174,'1996-06-18',9,34),(177,'1998-08-12',7,2);
/*!40000 ALTER TABLE `Sale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SaleAnimal`
--

DROP TABLE IF EXISTS `SaleAnimal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SaleAnimal` (
  `SaleID` int NOT NULL,
  `AnimalID` int NOT NULL,
  `SalePrice` float DEFAULT NULL,
  PRIMARY KEY (`SaleID`,`AnimalID`),
  KEY `fk_saleanimal_animal` (`AnimalID`),
  CONSTRAINT `fk_saleanimal_animal` FOREIGN KEY (`AnimalID`) REFERENCES `Animal` (`AnimalID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_saleanimal_sale` FOREIGN KEY (`SaleID`) REFERENCES `Sale` (`SaleID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SaleAnimal`
--

LOCK TABLES `SaleAnimal` WRITE;
/*!40000 ALTER TABLE `SaleAnimal` DISABLE KEYS */;
INSERT INTO `SaleAnimal` VALUES (4,8,183.38),(5,183,114.3),(6,58,132.19),(7,24,147.58),(8,42,174.27),(9,53,46.8),(10,9,1.8),(12,5,19.8),(13,162,119.88),(13,199,0),(15,13,10.8),(16,193,216.05),(17,11,148.47),(18,10,150.11),(20,13,10.8),(20,18,194.33),(21,163,234.93),(22,67,161.15),(23,189,153.07),(25,7,251.59),(26,64,229.73),(26,72,11.7),(27,6,173.99),(27,47,185.47),(28,36,171.41),(29,15,256.37),(30,198,128.41),(31,59,179.57),(32,16,246.92),(33,90,201.57),(34,46,229.37),(35,2,10.8),(36,17,149.16),(37,20,191.87),(38,23,97.49),(40,26,217.8),(41,27,172.88),(42,28,40.5),(43,31,148.23),(44,70,185.92),(45,196,179.55),(46,14,129.55),(46,45,164.5),(47,21,160.1),(47,32,125.03),(48,174,166.46),(49,29,13.5),(50,33,110.13),(50,200,108.33),(51,30,168.86),(52,40,193.34),(53,112,130.81),(54,37,103.86),(54,95,165.14),(55,35,182.8),(56,48,203.89),(57,43,214.12),(58,44,127.05),(59,39,190.16),(60,195,175.97),(61,49,197.06),(62,52,208.3),(64,50,240.35),(65,54,96.78),(66,147,245.27),(67,41,161.94),(67,55,184.25),(68,57,213.39),(69,56,204.66),(69,65,119.92),(70,66,247.6),(71,97,127.76),(72,60,293.13),(73,69,94.39),(74,63,172.15),(75,51,221.45),(76,71,107.71),(78,68,211.75),(79,74,115.28),(80,4,156.66),(81,79,202.03),(82,75,118.65),(83,82,112.5),(84,62,87.06),(85,80,76.01),(86,81,206.5),(87,73,111.29),(88,77,160.35),(88,86,19.8),(89,83,139.92),(90,89,138.55),(91,84,264.84),(92,91,154.44),(93,78,194.17),(94,93,128.77),(95,98,159.48),(96,61,199.4),(97,99,207.81),(98,87,187.31),(99,101,194.49),(100,96,217.51),(101,104,162.35),(101,111,214.7),(102,100,119.52),(103,191,210.09),(104,103,209.62),(105,109,115.4),(106,105,159.36),(107,102,177.59),(108,110,173.5),(109,115,189.79),(109,164,228.09),(110,108,152.22),(111,116,119.71),(112,118,3.6),(113,85,160.78),(114,94,201.04),(115,117,45),(116,119,182.31),(117,124,168.93),(118,122,254.56),(119,126,170.34),(120,113,128.36),(121,128,140.36),(122,121,256.09),(123,129,14.4),(124,106,198.85),(125,127,176.15),(126,133,295.36),(127,134,164.13),(128,120,195.23),(129,92,162.15),(130,136,186.1),(131,132,141.31),(132,131,192.96),(133,139,198.88),(134,141,211.06),(135,197,199.67),(136,137,214.67),(137,130,142.05),(137,143,146.76),(138,125,173.77),(139,145,181.67),(140,146,83.77),(141,148,232.11),(142,150,151.92),(142,160,159.12),(143,135,112.71),(144,151,219.78),(145,149,124.94),(145,153,211.09),(146,140,121.33),(147,156,252.53),(148,176,162.84),(149,138,209.22),(150,154,177.31),(151,152,196.95),(152,155,200.64),(152,166,147.35),(153,142,162.84),(154,144,164.34),(155,157,214.04),(156,159,127.78),(157,158,150.45),(158,161,220.41),(159,165,155.14),(160,168,203.12),(160,173,19.8),(161,169,119.24),(162,171,209.04),(162,172,218.06),(163,170,225.73),(164,180,24.3),(164,184,22.5),(165,178,148.17),(165,185,147.73),(166,175,173.83),(167,187,120.29),(167,190,147.24),(168,177,185.18),(169,179,125.65),(170,186,216.37),(171,182,196.64),(172,188,211.48),(173,192,177.19),(174,201,19.8),(177,19,189.59);
/*!40000 ALTER TABLE `SaleAnimal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SaleItem`
--

DROP TABLE IF EXISTS `SaleItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SaleItem` (
  `SaleID` int NOT NULL,
  `ItemID` int NOT NULL,
  `Quantity` int DEFAULT '0',
  `SalePrice` float DEFAULT '0',
  PRIMARY KEY (`SaleID`,`ItemID`),
  KEY `fk_saleitem_item` (`ItemID`),
  CONSTRAINT `fk_saleitem_item` FOREIGN KEY (`ItemID`) REFERENCES `Merchandise` (`ItemID`) ON DELETE RESTRICT,
  CONSTRAINT `fk_saleitem_sale` FOREIGN KEY (`SaleID`) REFERENCES `Sale` (`SaleID`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SaleItem`
--

LOCK TABLES `SaleItem` WRITE;
/*!40000 ALTER TABLE `SaleItem` DISABLE KEYS */;
INSERT INTO `SaleItem` VALUES (4,1,1,40.5),(4,36,1,19.8),(6,20,1,9),(6,21,1,22.5),(7,5,1,22.5),(7,19,3,7.2),(7,40,1,13.5),(8,16,100,0.72),(8,23,1,27),(8,36,1,19.8),(10,23,1,67.5),(10,25,5,2.7),(10,26,3,5.4),(10,27,1,31.5),(11,11,2,27),(11,15,20,0.36),(13,8,2,2.7),(13,38,1,7.2),(13,39,2,7.2),(14,18,50,0.9),(14,19,2,7.2),(15,25,1,2.7),(15,26,1,5.4),(17,3,1,76.5),(17,30,1,6.3),(17,33,1,13.5),(17,36,1,19.8),(17,37,1,7.2),(18,4,1,99),(19,3,1,76.5),(20,25,4,2.7),(21,11,3,27),(21,36,1,19.8),(23,21,1,22.5),(24,25,3,2.7),(24,26,2,5.4),(24,27,1,31.5),(27,3,1,76.5),(27,5,1,22.5),(27,11,2,27),(27,15,50,0.36),(27,18,10,0.9),(27,38,1,7.2),(30,37,1,7.2),(31,31,2,7.65),(31,33,1,13.5),(31,36,1,19.8),(32,30,3,6.3),(33,11,4,27),(33,15,100,0.36),(34,1,1,40.5),(34,32,1,10.8),(38,15,1,0.36),(38,16,1,0.72),(39,15,10,0.36),(39,17,10,0.45),(40,3,1,76.5),(40,16,5,0.72),(42,20,1,9),(42,23,1,67.5),(43,11,1,27),(44,34,1,18),(46,6,1,31.5),(46,18,1,0.9),(46,36,1,13.5),(47,21,1,22.5),(48,9,1,6.75),(48,15,5,0.36),(49,27,1,31.5),(54,1,1,40.5),(54,2,1,58.5),(54,17,1,0.45),(54,32,1,10.8),(54,38,1,7.2),(55,21,1,22.5),(57,20,1,9),(57,24,1,135),(57,28,1,22.5),(58,4,1,99),(58,11,1,27),(58,16,100,0.72),(60,10,1,14.4),(60,16,6,0.72),(63,17,10,0.45),(64,1,1,40.5),(64,7,1,3.6),(65,21,1,22.5),(66,7,1,3.6),(67,1,1,40.5),(67,2,1,58.5),(67,11,1,27),(67,16,12,0.72),(67,31,1,7.65),(67,36,2,19.8),(69,6,2,31.5),(69,8,2,2.7),(69,18,10,0.9),(69,38,2,7.2),(71,5,1,22.5),(73,10,1,14.4),(73,34,1,18),(74,17,5,0.45),(75,7,3,13.5),(75,33,1,13.5),(75,38,1,7.2),(77,23,1,67.5),(77,27,1,31.5),(79,16,20,0.9),(79,18,10,0.9),(79,32,1,10.8),(81,22,1,67.5),(83,2,1,58.5),(83,18,10,0.9),(83,20,1,9),(83,32,1,10.8),(83,36,1,19.8),(84,6,1,31.5),(85,9,1,6.75),(85,16,10,0.72),(85,32,1,10.8),(85,37,1,7.2),(87,30,2,6.3),(87,33,1,13.5),(87,36,1,19.8),(88,23,1,67.5),(88,26,1,5.4),(88,27,1,31.5),(89,5,1,22.5),(89,10,1,14.4),(90,7,2,3.6),(90,36,1,19.8),(91,17,5,0.45),(91,38,1,7.2),(93,10,1,14.4),(93,34,1,18),(93,36,1,67.5),(94,16,6,0.72),(95,2,1,58.5),(95,33,1,13.5),(97,20,1,9),(97,23,1,67.5),(99,3,1,76.5),(100,5,1,22.5),(104,18,7,0.9),(104,38,1,7.2),(106,17,4,0.45),(108,22,1,67.5),(109,8,1,2.7),(109,18,1,0.9),(109,21,1,22.5),(109,38,1,7.2),(112,23,1,67.5),(112,25,3,2.7),(112,27,1,31.5),(112,28,1,22.5),(113,6,1,31.5),(113,15,6,0.36),(118,18,1,0.9),(118,35,1,7.2),(118,38,1,7.2),(120,33,1,13.5),(120,36,1,19.8),(121,3,1,58.5),(121,34,1,18),(121,36,1,19.8),(123,28,1,22.5),(127,20,1,9),(127,23,1,67.5),(128,34,1,18),(128,36,1,19.8),(129,21,1,22.5),(131,37,1,7.2),(133,31,1,7.65),(133,34,1,18),(133,36,1,19.8),(134,36,1,19.8),(135,20,1,9),(135,23,1,67.5),(137,11,1,27),(137,14,1,16.2),(137,34,1,18),(137,36,1,19.8),(139,9,1,6.75),(139,38,1,7.2),(142,7,2,3.6),(142,11,1,27),(142,32,2,10.8),(142,36,2,19.8),(143,21,1,22.5),(144,22,1,67.5),(145,6,2,31.5),(145,8,3,2.7),(145,18,20,0.9),(145,38,2,7.2),(147,10,1,14.4),(147,33,1,13.5),(147,36,1,19.8),(148,10,1,14.4),(148,33,1,13.5),(148,36,1,19.8),(150,33,1,13.5),(150,36,1,19.8),(152,6,2,31.5),(152,17,5,0.45),(153,16,10,0.72),(155,22,1,67.5),(156,4,1,99),(156,14,2,6.3),(156,16,10,0.72),(157,34,1,10.8),(157,36,1,19.8),(158,4,1,76.5),(158,34,1,18),(158,36,1,19.8),(159,4,1,99),(160,1,1,40.5),(160,12,1,6.3),(162,7,2,3.6),(162,15,12,0.36),(162,33,2,13.5),(162,36,2,19.8),(164,24,1,135),(164,26,1,5.4),(164,27,1,31.5),(164,28,1,22.5),(165,1,1,40.5),(165,4,1,99),(165,11,1,27),(165,31,1,7.65),(165,32,1,10.8),(165,36,2,19.8),(167,10,2,14.4),(167,33,2,13.5),(167,36,1,19.8),(168,4,1,99),(170,11,1,27),(170,32,1,10.8),(170,36,1,19.8),(172,7,1,14.4),(172,16,1,2.7),(172,34,1,18),(172,36,1,19.8),(174,20,1,9),(174,23,1,67.5),(177,21,1,27.5);
/*!40000 ALTER TABLE `SaleItem` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-16 21:18:01