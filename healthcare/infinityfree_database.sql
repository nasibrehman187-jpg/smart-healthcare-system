-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: healthcare_system
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,1,'Test Action','CLI verification','2026-08-09 11:44:25'),(2,6,'Logged In',NULL,'2026-08-09 11:45:31'),(3,1,'Sent Warning','User #6','2026-08-09 11:45:48'),(4,3,'Logged In',NULL,'2026-08-09 11:46:43'),(5,6,'Booked Appointment','9','2026-08-09 12:02:29'),(6,6,'Rescheduled Appointment','9','2026-08-09 12:04:44'),(7,1,'Updated User Status','User #5 set to suspended','2026-08-09 12:05:33'),(8,1,'Updated User Status','User #5 set to active','2026-08-09 12:05:37'),(9,3,'Logged In',NULL,'2026-08-09 12:05:54'),(10,6,'Logged In',NULL,'2026-08-09 12:06:12'),(11,3,'Marked Bill Paid','Bill #3','2026-08-09 12:23:11'),(12,1,'Marked Bill Paid','Bill #2','2026-08-09 12:30:22'),(13,1,'Logged In',NULL,'2026-08-09 12:34:12'),(14,6,'Booked Appointment','12','2026-08-09 13:05:05'),(15,3,'Marked Bill Paid','Bill #4','2026-08-09 13:06:07'),(16,6,'Booked Appointment','15','2026-08-09 13:10:59'),(17,3,'Updated Appointment Status','Appt #15 set to Confirmed','2026-08-09 13:11:20'),(18,6,'Booked Appointment','22','2026-08-09 13:22:13'),(19,2,'Logged In',NULL,'2026-08-09 13:23:45'),(20,1,'Logged In',NULL,'2026-08-09 13:24:38'),(21,6,'Logged In',NULL,'2026-08-09 13:25:22'),(22,3,'Logged In',NULL,'2026-08-10 16:13:12'),(23,3,'Updated Profile','Doctor clinic profile updated','2026-08-10 16:14:29'),(24,3,'Updated Appointment Status','Appt #22 set to Confirmed','2026-08-10 16:14:48'),(25,6,'Logged In',NULL,'2026-08-10 16:15:33'),(26,3,'Updated Appointment Status','Appt #22 set to Completed','2026-08-10 16:24:14'),(27,1,'Logged In',NULL,'2026-08-10 16:25:35'),(28,6,'Booked Appointment','23','2026-08-10 16:31:37'),(29,3,'Updated Appointment Status','Appt #23 set to Confirmed','2026-08-10 16:36:06'),(30,3,'Updated Appointment Status','Appt #23 set to Completed','2026-08-10 16:36:13'),(31,6,'Booked Appointment','24','2026-08-10 16:37:16'),(32,5,'Logged In',NULL,'2026-08-10 16:38:20'),(33,1,'Logged In',NULL,'2026-08-10 16:38:30'),(34,5,'Updated Appointment Status','Appt #24 set to Confirmed','2026-08-10 16:38:43'),(35,6,'Booked Appointment','25','2026-08-10 16:39:11'),(36,3,'Logged In',NULL,'2026-08-10 17:21:33'),(37,1,'Logged In',NULL,'2026-08-12 07:57:15'),(38,7,'Registered','patient','2026-08-12 07:58:31'),(39,7,'Logged In',NULL,'2026-08-12 07:58:40'),(40,7,'Booked Appointment','27','2026-08-12 08:00:56'),(41,7,'Rescheduled Appointment','27','2026-08-12 08:01:47'),(42,7,'Cancelled Appointment','27','2026-08-12 08:02:11'),(43,3,'Logged In',NULL,'2026-08-12 08:03:42'),(44,1,'Updated User Status','User #7 set to suspended','2026-08-12 08:06:35'),(45,1,'Updated User Status','User #7 set to active','2026-08-12 08:07:44'),(46,7,'Logged In',NULL,'2026-08-12 08:07:53'),(47,1,'Logged In',NULL,'2026-08-12 08:13:25'),(48,1,'Updated User Status','User #7 set to suspended','2026-08-12 08:13:36'),(49,1,'Updated User Status','User #7 set to active','2026-08-12 08:21:53'),(50,7,'Logged In',NULL,'2026-08-12 08:22:14'),(51,1,'Updated User Status','User #7 set to suspended','2026-08-12 08:22:23'),(52,1,'Updated User Status','User #7 set to active','2026-08-12 08:22:34'),(53,7,'Logged In',NULL,'2026-08-12 08:23:04'),(54,1,'Updated User Status','User #7 set to suspended','2026-08-12 08:23:08'),(55,1,'Updated User Status','User #7 set to active','2026-08-12 08:23:28'),(56,7,'Logged In',NULL,'2026-08-12 08:23:48'),(57,1,'Sent Warning','User #7','2026-08-12 08:24:03'),(58,2,'Logged In',NULL,'2026-08-17 18:22:37'),(59,2,'Logged In',NULL,'2026-08-17 18:43:12'),(60,2,'Logged In',NULL,'2026-08-17 18:44:54'),(61,2,'Logged In',NULL,'2026-08-17 18:47:41'),(62,2,'Logged In',NULL,'2026-08-17 18:51:07'),(63,2,'Logged In',NULL,'2026-08-17 18:52:39'),(64,3,'Logged In',NULL,'2026-08-17 18:53:06'),(65,2,'Logged In',NULL,'2026-08-17 18:54:36'),(66,3,'Logged In',NULL,'2026-08-17 18:55:03'),(67,1,'Logged In',NULL,'2026-08-17 18:55:15'),(68,2,'Logged In',NULL,'2026-08-17 18:59:01'),(69,2,'Logged In',NULL,'2026-08-17 19:00:19'),(70,3,'Logged In',NULL,'2026-08-17 19:00:44'),(71,1,'Logged In',NULL,'2026-08-17 19:00:56'),(72,2,'Logged In',NULL,'2026-08-18 05:43:38'),(73,3,'Logged In',NULL,'2026-08-18 05:44:00'),(74,3,'Logged In',NULL,'2026-08-18 05:48:09'),(75,1,'Logged In',NULL,'2026-08-29 05:41:05'),(76,8,'Registered','patient','2026-08-29 05:52:37'),(77,8,'Logged In',NULL,'2026-08-29 05:52:45'),(78,8,'Booked Appointment','31','2026-08-29 05:55:59'),(79,3,'Logged In',NULL,'2026-08-29 05:57:52'),(80,3,'Updated Appointment Status','Appt #31 set to Confirmed','2026-08-29 05:58:54'),(81,3,'Marked Bill Paid','Bill #5','2026-08-29 06:00:27'),(82,1,'Logged In',NULL,'2026-08-29 06:06:46'),(83,1,'Marked Bill Paid','Bill #6','2026-08-29 06:09:25'),(84,8,'Logged In',NULL,'2026-08-29 06:09:47'),(85,2,'Logged In',NULL,'2026-08-29 06:10:13'),(86,1,'Logged In',NULL,'2026-08-29 07:35:40'),(87,3,'Logged In',NULL,'2026-08-29 07:36:33'),(88,8,'Logged In',NULL,'2026-08-29 07:37:42'),(89,8,'Booked Appointment','32','2026-08-29 07:39:39'),(90,3,'Logged In',NULL,'2026-08-29 07:39:57'),(91,3,'Updated Appointment Status','Appt #32 set to Confirmed','2026-08-29 07:40:08'),(92,9,'Registered','patient','2026-08-29 10:23:00'),(93,9,'Logged In',NULL,'2026-08-29 10:23:22'),(94,9,'Booked Appointment','33','2026-08-29 10:26:16'),(95,3,'Logged In',NULL,'2026-08-29 10:27:00'),(96,3,'Updated Appointment Status','Appt #33 set to Confirmed','2026-08-29 10:27:32'),(97,9,'Logged In',NULL,'2026-08-29 10:28:38'),(98,3,'Logged In',NULL,'2026-08-29 10:29:25'),(99,3,'Logged In',NULL,'2026-08-29 10:34:45'),(100,1,'Logged In',NULL,'2026-08-29 10:35:09'),(101,2,'Logged In',NULL,'2026-08-29 10:35:32'),(102,1,'Logged In',NULL,'2026-08-29 12:35:53'),(103,2,'Booked Appointment','34','2026-08-29 12:39:14'),(104,3,'Logged In',NULL,'2026-08-29 12:39:52'),(105,3,'Updated Appointment Status','Appt #31 set to Completed','2026-08-29 12:41:05');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `severity_level` enum('Emergency','Normal','Follow-up') NOT NULL,
  `appointment_time` datetime NOT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `symptoms_selected` varchar(500) DEFAULT NULL,
  `diagnosed_disease` varchar(100) DEFAULT NULL,
  `symptoms_text` text DEFAULT NULL,
  PRIMARY KEY (`appointment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,1,1,'Normal','2026-08-20 23:50:06','Confirmed','2026-08-08 08:09:55',NULL,NULL,NULL),(2,1,1,'Follow-up','2026-08-27 13:18:00','Cancelled','2026-08-08 08:18:51',NULL,NULL,NULL),(3,1,1,'Normal','2026-08-09 13:29:00','Cancelled','2026-08-08 08:29:41',NULL,NULL,NULL),(4,1,1,'Normal','2026-08-13 13:36:00','Cancelled','2026-08-08 08:36:02',NULL,NULL,NULL),(5,1,1,'Emergency','2026-08-08 13:43:00','Cancelled','2026-08-08 08:43:07',NULL,NULL,NULL),(6,1,3,'Emergency','2026-08-08 15:00:00','Completed','2026-08-08 09:40:47',NULL,NULL,NULL),(7,1,1,'Normal','2026-08-04 15:00:00','Cancelled','2026-08-08 10:57:02','fever,cough,headache,sore_throat','Flu (Influenza)',NULL),(8,1,1,'Normal','2026-08-09 00:00:00','Completed','2026-08-08 11:04:50','fever,cough,headache','Flu (Influenza)',NULL),(9,2,1,'Emergency','2026-08-09 17:05:00','Completed','2026-08-09 12:02:29','fever,cough','Respiratory Infection',NULL),(12,2,1,'Normal','2026-08-09 20:00:00','Completed','2026-08-09 13:05:05','stomach_pain','Food Poisoning','pait mein dard and qabz'),(15,2,1,'Normal','2026-08-09 18:15:00','Confirmed','2026-08-09 13:10:59','fever,cough,headache','Flu (Influenza)',NULL),(19,1,1,'Normal','2026-08-12 18:00:00','Confirmed','2026-08-09 13:18:01','fever','Flu',NULL),(22,2,1,'Normal','2026-08-10 19:00:00','Completed','2026-08-09 13:22:13','fever','Respiratory Infection',NULL),(23,2,1,'Emergency','2026-08-10 21:35:00','Completed','2026-08-10 16:31:37','','Unclear — General Checkup Needed','achanak se taangon mein dard ho raha hai'),(24,2,3,'Emergency','2026-08-10 21:40:00','Confirmed','2026-08-10 16:37:16','chest_pain','Cardiac Issue (Emergency)',NULL),(25,2,3,'Emergency','2026-08-10 21:45:00','Pending','2026-08-10 16:39:11','chest_pain','Cardiac Issue (Emergency)',NULL),(27,3,1,'Normal','2026-08-12 16:05:00','Cancelled','2026-08-12 08:00:56','cough,headache,body_ache','Flu (Influenza)','jism mein dard bhi hai, and better feel bhi nhn ho raha'),(28,1,1,'Emergency','2026-08-18 15:00:00','Confirmed','2026-08-18 05:41:31','chest_pain,shortness_of_breath,sweating','Cardiac Issue (Emergency)','Severe chest tightness and difficulty breathing'),(29,1,1,'Normal','2026-08-18 16:00:00','Pending','2026-08-18 05:41:31','fever,cough,headache','Flu (Influenza)','Persistent fever and mild dry cough for 3 days'),(30,1,1,'Normal','2026-08-18 11:05:00','Confirmed','2026-08-18 05:41:31','headache,nausea','Migraine','Sar dard aur matli ho rahi hai'),(31,4,1,'Normal','2026-08-29 14:00:00','Completed','2026-08-29 05:55:59','fever,cough,headache','Flu (Influenza)','Sar mien dard, chakar, kamzori'),(32,4,1,'Normal','2026-08-29 14:06:00','Confirmed','2026-08-29 07:39:39','fever,cough,headache','Flu (Influenza)','sar mein dard'),(33,5,1,'Normal','2026-08-29 16:25:00','Confirmed','2026-08-29 10:26:16','','Unclear — General Checkup Needed','Gupt Rog'),(34,1,1,'Normal','2026-08-30 15:30:00','Pending','2026-08-29 12:39:14','fever,cough,headache','Flu (Influenza)',NULL);
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billing`
--

DROP TABLE IF EXISTS `billing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billing` (
  `bill_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `consultation_fee` decimal(10,2) NOT NULL,
  `test_charges` decimal(10,2) DEFAULT 0.00,
  `insurance_discount_percent` decimal(5,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  PRIMARY KEY (`bill_id`),
  KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing`
--

LOCK TABLES `billing` WRITE;
/*!40000 ALTER TABLE `billing` DISABLE KEYS */;
INSERT INTO `billing` VALUES (1,6,1500.00,3500.00,0.00,5000.00,'2026-08-08 09:42:23','Paid'),(2,9,800.00,1500.00,0.00,2300.00,'2026-08-09 12:08:30','Paid'),(3,8,800.00,700.00,0.00,1500.00,'2026-08-09 12:16:37','Paid'),(4,12,800.00,250.00,0.00,1050.00,'2026-08-09 13:06:00','Paid'),(5,31,801.00,1500.00,0.00,2301.00,'2026-08-29 06:00:17','Paid'),(6,24,1500.00,1400.00,0.00,2900.00,'2026-08-29 06:08:58','Paid');
/*!40000 ALTER TABLE `billing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `diagnosis_rules`
--

DROP TABLE IF EXISTS `diagnosis_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `diagnosis_rules` (
  `rule_id` int(11) NOT NULL AUTO_INCREMENT,
  `symptom_combination` varchar(500) NOT NULL,
  `possible_disease` varchar(100) NOT NULL,
  `advice` text NOT NULL,
  `recommended_specialization` varchar(100) DEFAULT 'General Physician',
  `is_emergency` tinyint(1) DEFAULT 0,
  `first_aid_steps` text DEFAULT NULL,
  PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `diagnosis_rules`
--

LOCK TABLES `diagnosis_rules` WRITE;
/*!40000 ALTER TABLE `diagnosis_rules` DISABLE KEYS */;
INSERT INTO `diagnosis_rules` VALUES (1,'fever,cough,body_ache,headache','Flu (Influenza)','Rest well, drink plenty of fluids, and take over-the-counter fever reducers like paracetamol. See a doctor if symptoms persist beyond 3 days or if fever exceeds 103°F.','General Physician',0,NULL),(2,'fever,cough,shortness_of_breath','Respiratory Infection','Seek medical attention promptly. Avoid smoking and dusty environments. A chest X-ray may be recommended by your doctor. Monitor breathing difficulty closely.','Pulmonologist',0,NULL),(3,'headache,nausea,sensitivity_to_light','Migraine','Rest in a dark, quiet room. Stay hydrated and avoid screen time. Over-the-counter pain relievers may help. Consult a doctor if migraines occur frequently.','Neurologist',0,NULL),(4,'stomach_pain,vomiting,diarrhea,nausea','Food Poisoning','Stay hydrated with ORS (oral rehydration salts). Avoid solid food until vomiting stops. Eat bland foods when recovering. Seek medical help if symptoms last more than 24 hours.','Gastroenterologist',0,NULL),(5,'chest_pain,shortness_of_breath,sweating','Cardiac Issue (Emergency)','SEEK IMMEDIATE EMERGENCY MEDICAL ATTENTION. Do not delay — call an ambulance or go to the nearest emergency room immediately. Do not ignore chest pain combined with breathing difficulty.','Cardiologist',1,'1. Call emergency services (1122) or go to the nearest ER immediately.\n2. Sit down or lie down in a comfortable position, avoid exertion.\n3. Loosen any tight clothing.\n4. Stay calm and avoid panic — try slow, steady breathing.\n5. Do not drive yourself — have someone else drive or call an ambulance.\n6. Do NOT take any medication unless already prescribed by your doctor for this exact condition.'),(6,'fever,body_ache,rash,joint_pain,headache','Dengue Fever','Seek medical attention immediately. Stay hydrated with fluids. Do NOT take aspirin or ibuprofen as they can worsen bleeding. Platelet count monitoring is essential. Use mosquito nets.','General Physician',1,'1. Seek immediate medical evaluation at a hospital or clinic.\n2. Rest in a comfortable place and stay hydrated with clean water or ORS.\n3. Use mosquito nets to prevent further mosquito bites.\n4. Monitor closely for warning signs such as severe abdominal pain or bleeding.\n5. Do NOT take any unprescribed medications.'),(7,'sore_throat,fever,swollen_glands,headache','Throat Infection','Gargle with warm salt water 3-4 times daily. Stay hydrated with warm fluids. Avoid cold drinks. Antibiotics may be needed — consult a doctor for proper prescription. Do not self-medicate.','ENT Specialist',0,NULL),(8,'fever,cough,body_ache,headache','Flu (Influenza)','Rest well, drink plenty of fluids, and take over-the-counter fever reducers like paracetamol. See a doctor if symptoms persist beyond 3 days or if fever exceeds 103??F.','General Physician',0,NULL),(9,'fever,cough,shortness_of_breath','Respiratory Infection','Seek medical attention promptly. Avoid smoking and dusty environments. A chest X-ray may be recommended by your doctor. Monitor breathing difficulty closely.','Pulmonologist',0,NULL),(10,'headache,nausea,sensitivity_to_light','Migraine','Rest in a dark, quiet room. Stay hydrated and avoid screen time. Over-the-counter pain relievers may help. Consult a doctor if migraines occur frequently.','Neurologist',0,NULL),(11,'stomach_pain,vomiting,diarrhea,nausea','Food Poisoning','Stay hydrated with ORS (oral rehydration salts). Avoid solid food until vomiting stops. Eat bland foods when recovering. Seek medical help if symptoms last more than 24 hours.','Gastroenterologist',0,NULL),(12,'chest_pain,shortness_of_breath,sweating','Cardiac Issue (Emergency)','SEEK IMMEDIATE EMERGENCY MEDICAL ATTENTION. Do not delay ??? call an ambulance or go to the nearest emergency room immediately. Do not ignore chest pain combined with breathing difficulty.','Cardiologist',1,'1. Call emergency services (1122) or go to the nearest ER immediately.\n2. Sit down or lie down in a comfortable position, avoid exertion.\n3. Loosen any tight clothing.\n4. Stay calm and avoid panic — try slow, steady breathing.\n5. Do not drive yourself — have someone else drive or call an ambulance.\n6. Do NOT take any medication unless already prescribed by your doctor for this exact condition.'),(13,'fever,body_ache,rash,joint_pain,headache','Dengue Fever','Seek medical attention immediately. Stay hydrated with fluids. Do NOT take aspirin or ibuprofen as they can worsen bleeding. Platelet count monitoring is essential. Use mosquito nets.','General Physician',1,'1. Seek immediate medical evaluation at a hospital or clinic.\n2. Rest in a comfortable place and stay hydrated with clean water or ORS.\n3. Use mosquito nets to prevent further mosquito bites.\n4. Monitor closely for warning signs such as severe abdominal pain or bleeding.\n5. Do NOT take any unprescribed medications.'),(14,'sore_throat,fever,swollen_glands,headache','Throat Infection','Gargle with warm salt water 3-4 times daily. Stay hydrated with warm fluids. Avoid cold drinks. Antibiotics may be needed ??? consult a doctor for proper prescription. Do not self-medicate.','ENT Specialist',0,NULL);
/*!40000 ALTER TABLE `diagnosis_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `clinic_address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `available_from` time DEFAULT '09:00:00',
  `available_to` time DEFAULT '17:00:00',
  `consultation_fee` decimal(10,2) DEFAULT 500.00,
  PRIMARY KEY (`doctor_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctors`
--

LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT INTO `doctors` VALUES (1,3,'General Physician','Luqman Phatak','Khairpur','14:00:00','17:00:00',801.00),(2,4,'Dermatologist','Khairpur Sugar Mill Colony','Khairpur','09:00:00','17:00:00',1000.00),(3,5,'Cardiologist','Khairpur Sugar Mill Colony','Khairpur','09:00:00','17:00:00',1500.00);
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (1,'nasibrehman187@gmail.com','demo_recovery_token','2026-08-17 23:29:43','2026-08-17 17:29:43');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `age` int(11) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `cnic` varchar(15) NOT NULL,
  `insurance_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`patient_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1,2,18,52.00,'35302-3425158-9',NULL),(2,6,24,60.00,'33221-3333333-9',NULL),(3,7,18,40.00,'35444-8765188-9',NULL),(4,8,18,50.00,'33333-3333333-3',NULL),(5,9,30,65.00,'45203-6260146-9',NULL);
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remember_tokens`
--

DROP TABLE IF EXISTS `remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remember_tokens` (
  `token_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `selector` varchar(64) NOT NULL,
  `hashed_validator` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`token_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remember_tokens`
--

LOCK TABLES `remember_tokens` WRITE;
/*!40000 ALTER TABLE `remember_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `remember_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('patient','doctor','admin') NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','suspended') DEFAULT 'active',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System Administrator','admin@healthcare.com','$2y$10$9dXOog44ZzmYuA58FpqwnenymCsW7slx8yJV3tJOz1uXmaHj9wqqi','admin','03001234567','2026-08-08 07:58:34','active'),(2,'Nasib Rehman','nasibrehman187@gmail.com','$2y$10$HQUquXW9ikheqeW2NSLukOcCr3uVUnUyFCRV7jYlYeEws7hoawk76','patient','03062320099','2026-08-08 08:02:50','active'),(3,'AZHAR IQBAL','engrazhariqbal34@gmail.com','$2y$10$P3r6TGVMENnZeC5c3D68LO2chWAu8iFktG4eGvebcWttTZIOylz2e','doctor','03069364870','2026-08-08 08:09:34','active'),(4,'Faheem','faheemameen048@gmail.com','$2y$10$HQUquXW9ikheqeW2NSLukOcCr3uVUnUyFCRV7jYlYeEws7hoawk76','doctor','03296755422','2026-08-08 09:06:34','active'),(5,'Zain','zainjutt@gmail.com','$2y$10$HQUquXW9ikheqeW2NSLukOcCr3uVUnUyFCRV7jYlYeEws7hoawk76','doctor','03069999999','2026-08-08 09:13:55','active'),(6,'Admin','admin@gmail.com','$2y$10$HQUquXW9ikheqeW2NSLukOcCr3uVUnUyFCRV7jYlYeEws7hoawk76','patient','03062222222','2026-08-09 11:02:04','active'),(7,'Wahab Jutt','wahabjutt@gmail.com','$2y$10$HQUquXW9ikheqeW2NSLukOcCr3uVUnUyFCRV7jYlYeEws7hoawk76','patient','03062828887','2026-08-12 07:58:31','active'),(8,'Ali','ali@gmail.com','$2y$10$KnwqBrXewO.Qfzy5L1U6ceZ.cN4fzS2V5rb4vt.F5K.RdpiUpr.xe','patient','03000000000','2026-08-29 05:52:37','active'),(9,'ABid A;i','abid@gmail.com','$2y$10$7Pr/yiTOxezHZV4HaaEMVujrbBQl1YMdiuvHJVi1c5.BJ.dCdamom','patient','03130913962','2026-08-29 10:23:00','active');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warnings`
--

DROP TABLE IF EXISTS `warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warnings` (
  `warning_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`warning_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `warnings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warnings`
--

LOCK TABLES `warnings` WRITE;
/*!40000 ALTER TABLE `warnings` DISABLE KEYS */;
INSERT INTO `warnings` VALUES (1,6,'Hello',1,'2026-08-09 11:45:48'),(2,7,'warning',1,'2026-08-12 08:24:03');
/*!40000 ALTER TABLE `warnings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-30 15:34:05
