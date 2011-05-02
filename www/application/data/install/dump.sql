-- MySQL dump 10.13  Distrib 5.1.54, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: zcmf
-- ------------------------------------------------------
-- Server version	5.1.54-1ubuntu4

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `gallery`
--

DROP TABLE IF EXISTS `gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `pic` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`),
  KEY `pic` (`pic`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery`
--

LOCK TABLES `gallery` WRITE;
/*!40000 ALTER TABLE `gallery` DISABLE KEYS */;
INSERT INTO `gallery` VALUES (1,1,'Портфолио','<p>Здесь Вы можете увидеть наши работы</p>',12);
/*!40000 ALTER TABLE `gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery_items`
--

DROP TABLE IF EXISTS `gallery_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) NOT NULL,
  `gallery_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `pic` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`),
  KEY `gallery_id` (`gallery_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery_items`
--

LOCK TABLES `gallery_items` WRITE;
/*!40000 ALTER TABLE `gallery_items` DISABLE KEYS */;
INSERT INTO `gallery_items` VALUES (5,-3,1,'Фреймворк для быстрого создания сайтов на php',9,'http://zcmf.ru');
/*!40000 ALTER TABLE `gallery_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lists`
--

DROP TABLE IF EXISTS `lists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lists`
--

LOCK TABLES `lists` WRITE;
/*!40000 ALTER TABLE `lists` DISABLE KEYS */;
INSERT INTO `lists` VALUES (1,'Партнеры','<p>На этой странице Вы можете увидеть наших партнеров.</p>');
/*!40000 ALTER TABLE `lists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lists_items`
--

DROP TABLE IF EXISTS `lists_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lists_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `pic` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `orderid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `list_id` (`list_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lists_items`
--

LOCK TABLES `lists_items` WRITE;
/*!40000 ALTER TABLE `lists_items` DISABLE KEYS */;
INSERT INTO `lists_items` VALUES (5,1,'Магазин уникального дизайна',30,'http://webdesign-shop.ru/','<p>Здесь вы найдете различные по уровню сложности и цене авторские работы, выставленные на продажу различными дизайнерами. Каждый дизайн является уникальным и находится у автора в единственном экземпляре. Вместе с покупкой дизайна вам передаются права собственности на него.</p>',5);
INSERT INTO `lists_items` VALUES (6,1,'Талантливый дизайнер Сафонова Евгения',31,'http://jane-safo.ru/','<p>Цитата:&nbsp;Мне 27 лет, из них 2 года я работала дизайнером, 1&nbsp;год&nbsp;арт-директором, и 2 года дизайнером-фрилансером. Свою&nbsp;работу&nbsp;очень люблю, и намерена заниматься дизайном всю&nbsp;сознательную&nbsp;жизнь.</p>',4);
/*!40000 ALTER TABLE `lists_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu`
--

DROP TABLE IF EXISTS `menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu`
--

LOCK TABLES `menu` WRITE;
/*!40000 ALTER TABLE `menu` DISABLE KEYS */;
INSERT INTO `menu` VALUES (1,1,'Главная','/');
INSERT INTO `menu` VALUES (3,2,'Новости','/news/1');
INSERT INTO `menu` VALUES (4,3,'Партнеры','/list/1');
INSERT INTO `menu` VALUES (5,4,'Портфолио','/gallery/1');
INSERT INTO `menu` VALUES (6,5,'Видео','/video/1');
INSERT INTO `menu` VALUES (8,7,'Обратная связь','/feedback');
/*!40000 ALTER TABLE `menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `pic` int(11) NOT NULL,
  `description` text NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (19,1,'Комплексный миракль глазами современников','2011-03-14',0,'<p>Система координат изоморфна. Скалярное поле монотонно. Декаданс, в первом приближении, взвешивает параллельный гумус, дальнейшие выкладки оставим студентам в качестве несложной домашней работы.&nbsp;</p>','<p>Система координат изоморфна. Скалярное поле монотонно. Декаданс, в первом приближении, взвешивает параллельный гумус, дальнейшие выкладки оставим студентам в качестве несложной домашней работы. Кожух иллюстрирует атомный радиус даже в том случае, если непосредственное наблюдение этого явления затруднительно. Микрохроматический интервал продолжает угол тангажа независимо от последствий проникновения метилкарбиола внутрь.</p>\r\n<p>Проекция абсолютной угловой скорости на оси системы координат xyz, как можно показать с помощью не совсем тривиальных вычислений, одномерно захватывает эксикатор, что отчасти объясняет такое количество кавер-версий. Развивая эту тему, типическое активно. Прямоугольная матрица, по определению, увлажняет комплекс априорной бисексуальности, именно об этом комплексе движущих сил писал З.Фрейд в теории сублимации. Период изящно продолжает периодический экзистенциализм, что обусловлено малыми углами карданового подвеса. Ф.Шилер, Г.Гете, Ф.Шлегели и А.Шлегели выразили типологическую антитезу классицизма и романтизма через противопоставление искусства \"наивного\" и \"сентиментального\", поэтому алеаторически выстроенный бесконечный канон с полизеркальной векторно-голосовой структурой кисло варьирует определенный ортштейн, составляя уравнения Эйлера для этой системы координат.</p>\r\n<p>Стяжение, в согласии с традиционными представлениями, вертикально вызывает равновесный интеграл от функции, обращающейся в бесконечность в изолированной точке, дальнейшие выкладки оставим студентам в качестве несложной домашней работы. Осушение эллиптично передает собственный кинетический момент, откуда следует доказываемое равенство. Дифференциация транслирует символизм, потому что современная музыка не запоминается. Внутридискретное арпеджио стабилизирует многочлен, благодаря быстрой смене тембров (каждый инструмент играет минимум звуков).</p>');
INSERT INTO `news` VALUES (20,1,'Почему относительно уравнение возмущенного движения?','2011-03-15',22,'<p>Ионообменник привлекает убывающий интеграл по поверхности, и этот процесс может повторяться многократно. Агробиогеоценоз, в том числе, интуитивно понятен. Гомолог, как бы это ни казалось парадоксальным, основан&nbsp;на&nbsp;тщательном анализе.&nbsp;</p>','<p>Ионообменник привлекает убывающий интеграл по поверхности, и этот процесс может повторяться многократно. Агробиогеоценоз, в том числе, интуитивно понятен. Гомолог, как бы это ни казалось парадоксальным, основан&nbsp;на&nbsp;тщательном анализе. Культовый образ многопланово просветляет лессиваж, вне зависимости от предсказаний теоретической модели явления. Голос имеет уход гироскопа, подобный исследовательский подход к проблемам художественной типологии можно обнаружить у К.Фосслера.</p>\r\n<p>Прекрасное, на первый взгляд, непрерывно. Кора выветривания, как того требуют законы термодинамики, возможна. В соответствии с принципом неопределенности, векторное поле расточительно адсорбирует возбужденный эффект \"вау-вау\", как и предполагалось. Система координат сонорна.</p>\r\n<p>При переходе к следующему уровню организации почвенного покрова ожелезнение относительно. Прибор образует такыровидный кристаллизатор по мере распространения использования фтористого этилена. Однако не все знают, что винил начинает элитарный флегматик, поглощая их в количестве сотен и тысяч процентов от собственного исходного объема. Целое число стабилизирует комплексный микроагрегат, как и реверансы в сторону ранних \"роллингов\". С точки зрения теории строения атомов, относительная погрешность трудна в описании.</p>');
INSERT INTO `news` VALUES (21,1,'Определенный рок-н-ролл 50-х: предпосылки и развитие','2011-03-16',23,'<p>Сходящийся ряд позитивно начинает контрпример, но здесь диспергированные частицы исключительно малы. Очевидно, что нутация многопланово образует резкий глей, что и требовалось доказать. Ротор, в первом приближении, изменяем.&nbsp;</p>','<p>Сходящийся ряд позитивно начинает контрпример, но здесь диспергированные частицы исключительно малы. Очевидно, что нутация многопланово образует резкий глей, что и требовалось доказать. Ротор, в первом приближении, изменяем. Суммарный поворот интуитивно понятен.</p>\r\n<p>Реакция синхронно продолжает реализм, таким образом сбылась мечта идиота - утверждение полностью доказано. Ракета, согласно традиционным представлениям, стекает в флэнжер, как и реверансы в сторону ранних \"роллингов\". Ускорение, согласно традиционным представлениям, образует серный эфир, потому что современная музыка не запоминается. Действительно, аккорд изменяет пылеватый хамбакер, таким образом, сходные законы контрастирующего развития характерны и для процессов в психике.</p>\r\n<p>Волчок имитирует динамический комплекс рения с саленом, так Г.Корф формулирует собственную антитезу. Следуя механической логике, интеграл от переменной величины подвержен. Интеграл по бесконечной области, как неоднократно наблюдалось при постоянном воздействии ультрафиолетового облучения, использует абстрактный агробиогеоценоз, что-то подобное можно встретить в работах Ауэрбаха и Тандлера. Реакция, несмотря на внешние воздействия, имеет окисленный катионит, что несомненно приведет нас к истине.</p>');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_groups`
--

DROP TABLE IF EXISTS `news_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_groups`
--

LOCK TABLES `news_groups` WRITE;
/*!40000 ALTER TABLE `news_groups` DISABLE KEYS */;
INSERT INTO `news_groups` VALUES (1,'Новости','<p>Тут вы можете увидеть новости нашей компании. Заходите чаще на эту страницу, чтобы быть в курсе всех последних событий!</p>');
/*!40000 ALTER TABLE `news_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video`
--

DROP TABLE IF EXISTS `video`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video`
--

LOCK TABLES `video` WRITE;
/*!40000 ALTER TABLE `video` DISABLE KEYS */;
INSERT INTO `video` VALUES (1,'Видеоуроки','<p>В этом разделе Вы сможете посмотреть различные обучающие видеоуроки.</p>');
/*!40000 ALTER TABLE `video` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_items`
--

DROP TABLE IF EXISTS `video_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `code` text NOT NULL,
  `video_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `video_id` (`video_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_items`
--

LOCK TABLES `video_items` WRITE;
/*!40000 ALTER TABLE `video_items` DISABLE KEYS */;
INSERT INTO `video_items` VALUES (3,'2011-03-16','Zend Framework DB Access','','<iframe title=\"YouTube video player\" width=\"480\" height=\"390\" src=\"http://www.youtube.com/embed/vFMh2oeob18?rel=0\" frameborder=\"0\" allowfullscreen></iframe>',1);
INSERT INTO `video_items` VALUES (4,'2011-03-15','Zend Framework: Zend_Auth','','<iframe title=\"YouTube video player\" width=\"480\" height=\"390\" src=\"http://www.youtube.com/embed/ZLzub1xKO2o?rel=0\" frameborder=\"0\" allowfullscreen></iframe>',1);
INSERT INTO `video_items` VALUES (5,'2011-03-14','Building an Ajax Interface with Zend Framework ','','<iframe title=\"YouTube video player\" width=\"480\" height=\"390\" src=\"http://www.youtube.com/embed/gvthjfCyTUw?rel=0\" frameborder=\"0\" allowfullscreen></iframe>',1);
/*!40000 ALTER TABLE `video_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_config`
--

DROP TABLE IF EXISTS `z_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `crated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(255) NOT NULL,
  `sid` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `crated_on` (`crated_on`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_config`
--

LOCK TABLES `z_config` WRITE;
/*!40000 ALTER TABLE `z_config` DISABLE KEYS */;
INSERT INTO `z_config` VALUES (6,'2010-04-20 09:16:04','Копирайты','copy','text','<a href=\"http://jane-safo.ru\">Дизайн</a>  Евгения Сафонова<br />\r\n<a href=\"http://cramen.ru\">Верстка и программирование</a> Антон Еськин<br />\r\n<strong>©ramen 2009-2011 ZCMF</strong>');
INSERT INTO `z_config` VALUES (13,'2010-05-15 07:13:48','Текст страницы ошибки','error_text','html','<p>Здравствуйте!</p>\r\n<p>К сожалению запрашиваемая Вами страница не существует на нашем сайте.</p>\r\n<p>Это могло произойти по одной из причин:</p>\r\n<ul>\r\n<li>Вы ошиблись при наборе адреса страницы</li>\r\n<li>Перешли по неработающей(битой) ссылке</li>\r\n<li>Запрашиваемая страница была удалена</li>\r\n</ul>\r\n<p>Мы просим прощения за предоставленные неудобства и предлагаем следующие варианты:</p>\r\n<ul>\r\n<li>вернуться назад при помощи кнопки браузера back</li>\r\n<li>проверить правильность написания адреса страницы(URL) в адресной строке браузера</li>\r\n<li>перейти на <a href=\"/\">главную страницу</a> сайта</li>\r\n<li>посетить основные разделы сайта используя главное меню сайта</li>\r\n</ul>\r\n<p>Если Вы уверены в правильности набранного адреса страницы и считаете, что эта ошибка произошла по нашей вине, пожалуйста, сообщите об этом нам при помощи <a href=\"/feedback\">формы обратной связи</a>.</p>');
INSERT INTO `z_config` VALUES (16,'2010-05-31 07:09:16','Счетчик','counter','text','<a href=\"http://validator.w3.org/check?uri=referer\"><img src=\"http://www.w3.org/Icons/valid-xhtml10\" alt=\"Valid XHTML 1.0 Strict\" height=\"31\" width=\"88\" /></a>');
INSERT INTO `z_config` VALUES (18,'2010-06-01 09:26:25','E-Mail администратора','email','string','');
INSERT INTO `z_config` VALUES (19,'2011-03-03 06:47:56','robots.txt','robots.txt','text','User-agent:*\r\nDisallow: /redirect*\r\nDisallow: /captcha*\r\n\r\nUser-agent: Yandex\r\nDisallow: /redirect*\r\nDisallow: /captcha*\r\n');
INSERT INTO `z_config` VALUES (20,'2011-03-15 13:06:42','Текст, отображаемый после отправки формы обратной связи','feedback_text','html','<p>Благодарим Вас за письмо. Мы обязательно свяжемся с Вами.</p>');
/*!40000 ALTER TABLE `z_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_dbtables`
--

DROP TABLE IF EXISTS `z_dbtables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_dbtables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_dbtables`
--

LOCK TABLES `z_dbtables` WRITE;
/*!40000 ALTER TABLE `z_dbtables` DISABLE KEYS */;
INSERT INTO `z_dbtables` VALUES (1,'menu');
INSERT INTO `z_dbtables` VALUES (2,'news_groups');
INSERT INTO `z_dbtables` VALUES (3,'news');
INSERT INTO `z_dbtables` VALUES (5,'lists');
INSERT INTO `z_dbtables` VALUES (6,'lists_items');
INSERT INTO `z_dbtables` VALUES (7,'gallery');
INSERT INTO `z_dbtables` VALUES (8,'gallery_items');
INSERT INTO `z_dbtables` VALUES (9,'video');
INSERT INTO `z_dbtables` VALUES (10,'video_items');
/*!40000 ALTER TABLE `z_dbtables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_dbtables_fields`
--

DROP TABLE IF EXISTS `z_dbtables_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_dbtables_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dbtable_id` int(11) NOT NULL,
  `orderid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `len` int(11) NOT NULL,
  `default` varchar(255) NOT NULL,
  `default_value` varchar(255) NOT NULL,
  `is_null` int(1) NOT NULL DEFAULT '0',
  `is_index` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `dbtable_id` (`dbtable_id`),
  KEY `orderid` (`orderid`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_dbtables_fields`
--

LOCK TABLES `z_dbtables_fields` WRITE;
/*!40000 ALTER TABLE `z_dbtables_fields` DISABLE KEYS */;
INSERT INTO `z_dbtables_fields` VALUES (1,1,1,'orderid','int',11,'no','',0,1);
INSERT INTO `z_dbtables_fields` VALUES (2,1,2,'title','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (3,1,3,'url','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (4,2,4,'title','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (5,2,5,'description','text',0,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (6,3,7,'title','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (7,3,8,'date','date',0,'no','',0,1);
INSERT INTO `z_dbtables_fields` VALUES (8,3,9,'pic','int',11,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (9,3,10,'description','text',0,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (10,3,11,'text','text',0,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (11,3,6,'group_id','int',11,'no','',0,1);
INSERT INTO `z_dbtables_fields` VALUES (13,5,12,'title','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (14,6,13,'list_id','int',11,'no','',0,1);
INSERT INTO `z_dbtables_fields` VALUES (15,6,14,'title','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (16,6,15,'pic','int',11,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (17,6,16,'url','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (18,6,17,'text','text',0,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (20,5,18,'description','text',0,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (21,6,19,'orderid','int',11,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (22,7,21,'title','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (23,7,22,'description','text',0,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (24,7,20,'orderid','int',11,'no','',0,1);
INSERT INTO `z_dbtables_fields` VALUES (25,8,25,'description','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (26,8,26,'pic','int',11,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (27,8,23,'orderid','int',11,'no','',0,1);
INSERT INTO `z_dbtables_fields` VALUES (28,8,24,'gallery_id','int',11,'no','',0,1);
INSERT INTO `z_dbtables_fields` VALUES (29,8,27,'url','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (30,7,28,'pic','int',11,'no','',0,1);
INSERT INTO `z_dbtables_fields` VALUES (31,9,29,'title','varchar',255,'no','',0,1);
INSERT INTO `z_dbtables_fields` VALUES (32,9,30,'description','text',0,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (33,10,31,'date','date',0,'no','',0,1);
INSERT INTO `z_dbtables_fields` VALUES (34,10,32,'title','varchar',255,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (35,10,33,'description','text',0,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (36,10,34,'code','text',0,'no','',0,0);
INSERT INTO `z_dbtables_fields` VALUES (37,10,35,'video_id','int',11,'no','',0,1);
/*!40000 ALTER TABLE `z_dbtables_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_geo_areas`
--

DROP TABLE IF EXISTS `z_geo_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_geo_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area` varchar(255) DEFAULT NULL,
  `z_geo_districts_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_z_geo_areas_z_geo_districts1` (`z_geo_districts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_geo_areas`
--

LOCK TABLES `z_geo_areas` WRITE;
/*!40000 ALTER TABLE `z_geo_areas` DISABLE KEYS */;
/*!40000 ALTER TABLE `z_geo_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_geo_blocks`
--

DROP TABLE IF EXISTS `z_geo_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_geo_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start` int(12) unsigned DEFAULT NULL,
  `stop` int(12) unsigned DEFAULT NULL,
  `z_geo_cityes_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `start` (`start`),
  KEY `stop` (`stop`),
  KEY `fk_z_geo_ip_z_geo_cityes1` (`z_geo_cityes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_geo_blocks`
--

LOCK TABLES `z_geo_blocks` WRITE;
/*!40000 ALTER TABLE `z_geo_blocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `z_geo_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_geo_cityes`
--

DROP TABLE IF EXISTS `z_geo_cityes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_geo_cityes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city` varchar(255) DEFAULT NULL,
  `z_geo_areas_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_z_geo_cityes_z_geo_areas1` (`z_geo_areas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_geo_cityes`
--

LOCK TABLES `z_geo_cityes` WRITE;
/*!40000 ALTER TABLE `z_geo_cityes` DISABLE KEYS */;
/*!40000 ALTER TABLE `z_geo_cityes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_geo_districts`
--

DROP TABLE IF EXISTS `z_geo_districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_geo_districts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `district` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_geo_districts`
--

LOCK TABLES `z_geo_districts` WRITE;
/*!40000 ALTER TABLE `z_geo_districts` DISABLE KEYS */;
/*!40000 ALTER TABLE `z_geo_districts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_log`
--

DROP TABLE IF EXISTS `z_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `priorityName` varchar(16) NOT NULL,
  `priority` tinyint(4) DEFAULT NULL,
  `message` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_log`
--

LOCK TABLES `z_log` WRITE;
/*!40000 ALTER TABLE `z_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `z_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_mails`
--

DROP TABLE IF EXISTS `z_mails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_mails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` varchar(255) CHARACTER SET cp1251 NOT NULL,
  `title` varchar(255) CHARACTER SET cp1251 NOT NULL,
  `description` text CHARACTER SET cp1251 NOT NULL,
  `message` text CHARACTER SET cp1251 NOT NULL,
  `from` varchar(255) CHARACTER SET cp1251 NOT NULL,
  `to` varchar(255) CHARACTER SET cp1251 NOT NULL,
  `theme` varchar(255) CHARACTER SET cp1251 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sid` (`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_mails`
--

LOCK TABLES `z_mails` WRITE;
/*!40000 ALTER TABLE `z_mails` DISABLE KEYS */;
INSERT INTO `z_mails` VALUES (1,'feedback_admin','Шаблон письма администратору при отправке формы обратной связи','<div>\r\n<p>{{fio}} - ФИО</p>\r\n</div>\r\n<p>{{email}} - email отправителя</p>\r\n<div>\r\n<p>{{text}} - текст письма</p>\r\n</div>','<p><strong>Вам написал(а) на сайте письмо </strong>{{fio}}</p>\r\n<p>email отправителя: {{email}}</p>\r\n<p>текст письма:</p>\r\n<p>{{text}}</p>','','','Новое письмо на сайте!');
/*!40000 ALTER TABLE `z_mails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_menu`
--

DROP TABLE IF EXISTS `z_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) DEFAULT NULL,
  `parentid` int(11) DEFAULT NULL,
  `title` varchar(45) DEFAULT NULL,
  `controller` varchar(45) DEFAULT NULL,
  `action` varchar(45) DEFAULT NULL,
  `visible` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`),
  KEY `parentid` (`parentid`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_menu`
--

LOCK TABLES `z_menu` WRITE;
/*!40000 ALTER TABLE `z_menu` DISABLE KEYS */;
INSERT INTO `z_menu` VALUES (3,3,0,'Настройки','z_config','list',1);
INSERT INTO `z_menu` VALUES (4,5,0,'Публикации','z_statpage','list',1);
INSERT INTO `z_menu` VALUES (5,20,0,'Структура','structure','',1);
INSERT INTO `z_menu` VALUES (6,12,5,'Меню админки','z_menuconstructor','list',1);
INSERT INTO `z_menu` VALUES (7,22,0,'Участники','partyes','',1);
INSERT INTO `z_menu` VALUES (8,8,7,'Пользователи','z_users','list',1);
INSERT INTO `z_menu` VALUES (9,9,7,'Роли','acl_roles','list',1);
INSERT INTO `z_menu` VALUES (10,10,7,'Ресурсы','acl_resources','list',1);
INSERT INTO `z_menu` VALUES (11,11,7,'Привилегии','acl_privileges','list',1);
INSERT INTO `z_menu` VALUES (12,19,5,'База данных','z_database','',1);
INSERT INTO `z_menu` VALUES (19,6,5,'Сео','z_seo','list',1);
INSERT INTO `z_menu` VALUES (21,21,5,'Структура','z_structure','list',1);
INSERT INTO `z_menu` VALUES (25,7,0,'Шаблоны писем','z_mails','list',1);
INSERT INTO `z_menu` VALUES (37,37,0,'Очистить кэш','z_cleancache','index',1);
/*!40000 ALTER TABLE `z_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_privileges`
--

DROP TABLE IF EXISTS `z_privileges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_privileges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_privileges`
--

LOCK TABLES `z_privileges` WRITE;
/*!40000 ALTER TABLE `z_privileges` DISABLE KEYS */;
INSERT INTO `z_privileges` VALUES (1,'add','Добавление');
INSERT INTO `z_privileges` VALUES (2,'edit','Редактирование');
INSERT INTO `z_privileges` VALUES (3,'delete','Удаление');
INSERT INTO `z_privileges` VALUES (4,'list','Просмотр');
INSERT INTO `z_privileges` VALUES (6,'reorder','Перемещение');
INSERT INTO `z_privileges` VALUES (7,'','Все');
INSERT INTO `z_privileges` VALUES (8,'view_menu','Просмотр пункта меню');
/*!40000 ALTER TABLE `z_privileges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_privileges_connect`
--

DROP TABLE IF EXISTS `z_privileges_connect`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_privileges_connect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `privilege_id` int(11) NOT NULL,
  `rule_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_privilege_id` (`privilege_id`),
  KEY `fk_rule_id` (`rule_id`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_privileges_connect`
--

LOCK TABLES `z_privileges_connect` WRITE;
/*!40000 ALTER TABLE `z_privileges_connect` DISABLE KEYS */;
INSERT INTO `z_privileges_connect` VALUES (97,7,29);
INSERT INTO `z_privileges_connect` VALUES (108,7,38);
INSERT INTO `z_privileges_connect` VALUES (109,7,39);
INSERT INTO `z_privileges_connect` VALUES (110,7,41);
INSERT INTO `z_privileges_connect` VALUES (112,1,43);
INSERT INTO `z_privileges_connect` VALUES (113,3,43);
INSERT INTO `z_privileges_connect` VALUES (114,7,44);
INSERT INTO `z_privileges_connect` VALUES (143,1,45);
INSERT INTO `z_privileges_connect` VALUES (144,3,45);
INSERT INTO `z_privileges_connect` VALUES (145,7,46);
INSERT INTO `z_privileges_connect` VALUES (157,7,42);
INSERT INTO `z_privileges_connect` VALUES (158,7,47);
INSERT INTO `z_privileges_connect` VALUES (159,7,48);
/*!40000 ALTER TABLE `z_privileges_connect` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_resources`
--

DROP TABLE IF EXISTS `z_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resourceId` varchar(255) NOT NULL,
  `actionId` varchar(255) NOT NULL DEFAULT '',
  `parentid` int(11) DEFAULT '0',
  `orderid` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `datatype` varchar(255) NOT NULL,
  `indexate` varchar(255) NOT NULL,
  `default_field` varchar(255) NOT NULL,
  `parent_field` varchar(255) NOT NULL,
  `order` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `paginate` int(11) NOT NULL,
  `can_delete` int(1) NOT NULL DEFAULT '1',
  `can_edit` int(1) NOT NULL DEFAULT '1',
  `can_add` int(1) NOT NULL DEFAULT '1',
  `delete_confirm` int(1) NOT NULL DEFAULT '1',
  `delete_on_have_child` int(1) NOT NULL DEFAULT '0',
  `sortable` int(1) NOT NULL DEFAULT '0',
  `sortable_position` varchar(255) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `on_have_subcat` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `resourceId` (`resourceId`),
  KEY `parentid` (`parentid`),
  KEY `orderid` (`orderid`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_resources`
--

LOCK TABLES `z_resources` WRITE;
/*!40000 ALTER TABLE `z_resources` DISABLE KEYS */;
INSERT INTO `z_resources` VALUES (8,'z_users','list',24,8,'Пользователи','Z_Model_Users','band','','login','','login asc','',15,1,1,1,1,0,0,'',1,1);
INSERT INTO `z_resources` VALUES (11,'acl_roles','list',24,11,'Роли','Z_Model_Roles','band','','title','','title asc','',15,1,1,1,1,0,0,'',1,1);
INSERT INTO `z_resources` VALUES (12,'acl_resources','list',23,34,'Ресурсы','Z_Model_Resources','catalog','','title','','orderid','',15,1,1,1,1,1,1,'bottom',1,1);
INSERT INTO `z_resources` VALUES (13,'acl_privileges','list',24,13,'Привилегии','Z_Model_Privileges','band','','title','','title asc','',15,1,1,1,1,0,0,'',1,1);
INSERT INTO `z_resources` VALUES (18,'acl_parentroles','',11,18,'Родительские роли','Z_Model_Roles_Connect','band','','child_role_id','child_role_id','','',15,1,1,1,1,0,1,'bottom',0,1);
INSERT INTO `z_resources` VALUES (19,'acl_rules','',11,19,'Правила','Z_Model_Rules','band','','id','role_id','title asc','',15,1,1,1,1,0,0,'',0,1);
INSERT INTO `z_resources` VALUES (21,'z_statpage','list',53,32,'Публикации','Z_Model_Statpage','band','','title','','title','',15,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (22,'z_config','list',53,7,'Настройки','Z_Model_Config','band','','title','','title asc','',15,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (23,'adminstrucsure','',0,53,'Конструктор','','band','','','','','',0,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (24,'partyes','',0,49,'Участники','','band','','','','','',0,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (31,'filemanager','',0,69,'Файловый менеджер','','band','','','','','',0,1,1,1,1,0,0,'bottom',0,1);
INSERT INTO `z_resources` VALUES (32,'z_seo','list',0,31,'SEO','Z_Model_Titles','band','','title','','','',15,1,1,1,1,0,1,'bottom',1,1);
INSERT INTO `z_resources` VALUES (49,'z_cleancache','',53,82,'Очистить кэш','','','','','','','',0,1,1,1,1,0,0,'',1,1);
INSERT INTO `z_resources` VALUES (50,'','',0,73,'Все','','','','','','','',0,1,1,1,1,0,0,'',0,1);
INSERT INTO `z_resources` VALUES (53,'site','',0,26,'Сайт','','band','','','','','',0,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (57,'z_mails','list',53,78,'Шаблоны писем','Z_Model_Mails','band','','title','','title asc','',15,1,1,1,1,0,0,'',1,1);
INSERT INTO `z_resources` VALUES (59,'acl_resourcecolumns','list',12,59,'Колонки','Z_Model_Resourcecolumns','band','','title','resourceid','orderid','',15,1,1,1,1,0,1,'bottom',0,1);
INSERT INTO `z_resources` VALUES (60,'acl_resourceconditions','list',12,61,'Условия','Z_Model_Resourceconditions','band','','condition','resourceid','id','',15,1,1,1,1,0,0,'bottom',0,1);
INSERT INTO `z_resources` VALUES (61,'acl_resourcejoins','list',12,64,'Джойны','Z_Model_Resourcejoins','band','','model','resourceid','orderid','',15,1,1,1,1,0,1,'bottom',0,1);
INSERT INTO `z_resources` VALUES (62,'acl_resourceforms','list',12,60,'Форма','Z_Model_Resourceforms','band','','label','resourceid','orderid','',20,1,1,1,1,0,1,'bottom',0,1);
INSERT INTO `z_resources` VALUES (63,'acl_resourceformsparams','list',62,63,'Параметры','Z_Model_Resourceformsparams','band','','title','formid','title','',15,1,1,1,1,0,0,'bottom',0,1);
INSERT INTO `z_resources` VALUES (64,'acl_resourcerefers','list',12,62,'Связи','Z_Model_Resourcerefers','band','','field','resourceid','field','',15,1,1,1,1,0,0,'bottom',0,1);
INSERT INTO `z_resources` VALUES (65,'acl_resourcebuttons','list',12,65,'Кнопки','Z_Model_Resourcebuttons','band','','title','resourceid','orderid','',15,1,1,1,1,0,1,'bottom',0,1);
INSERT INTO `z_resources` VALUES (66,'z_dbtables','list',23,66,'Конструктор БД','Z_Model_Dbtables','band','','title','','title asc','',15,1,1,1,1,1,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (67,'z_dbtablesfields','list',66,67,'Поля','Z_Model_Dbtablesfields','band','','title','dbtable_id','orderid','',15,1,1,1,1,0,1,'bottom',0,1);
INSERT INTO `z_resources` VALUES (71,'system','',0,68,'Система','','band','','title','','id','',15,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (72,'z_phpinfo','index',71,71,'PHPinfo','','band','','title','','id','',15,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (74,'z_adminpanel','list',0,80,'Панель администрирования сайта','','band','','title','','id','',15,1,1,1,1,0,0,'bottom',0,1);
INSERT INTO `z_resources` VALUES (75,'menu','list',53,21,'Меню','Site_Model_Menu','band','','title','','orderid','',20,1,1,1,1,0,1,'bottom',1,1);
INSERT INTO `z_resources` VALUES (76,'filesystem','index',23,75,'Файловый навигатор','','band','','title','','id','',15,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (77,'newsgroups','list',53,57,'Группы новостей','Site_Model_News_Groups','band','','title','','id','',15,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (78,'news','list',77,77,'Новости','Site_Model_News','band','','title','group_id','date desc','',15,1,1,1,1,0,0,'bottom',0,1);
INSERT INTO `z_resources` VALUES (79,'lists','list',53,72,'Списки','Site_Model_Lists','band','','title','','id','',15,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (80,'lists_items','list',79,79,'Элементы списка','Site_Model_Lists_Items','band','','title','list_id','orderid','',15,1,1,1,1,0,1,'top',0,1);
INSERT INTO `z_resources` VALUES (81,'gallery','list',53,74,'Галерея','Site_Model_Gallery','band','','title','','orderid','',15,1,1,1,1,0,1,'top',1,1);
INSERT INTO `z_resources` VALUES (82,'gallery_items','list',81,81,'Элементы (Изображения)','Site_Model_Gallery_Items','band','','id','gallery_id','orderid','',15,1,1,1,1,0,1,'top',0,1);
INSERT INTO `z_resources` VALUES (83,'video','list',53,76,'Видео','Site_Model_Video','band','','title','','id','',15,1,1,1,1,0,0,'bottom',1,1);
INSERT INTO `z_resources` VALUES (84,'video_items','list',83,83,'Видео','Site_Model_Video_Items','band','','title','video_id','date desc','',15,1,1,1,1,0,0,'bottom',0,1);
/*!40000 ALTER TABLE `z_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_resources_buttons`
--

DROP TABLE IF EXISTS `z_resources_buttons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_resources_buttons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) NOT NULL,
  `resourceid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` text NOT NULL,
  `class` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`),
  KEY `resourceid` (`resourceid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_resources_buttons`
--

LOCK TABLES `z_resources_buttons` WRITE;
/*!40000 ALTER TABLE `z_resources_buttons` DISABLE KEYS */;
INSERT INTO `z_resources_buttons` VALUES (1,1,66,'Сгенерировать','return array();','');
/*!40000 ALTER TABLE `z_resources_buttons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_resources_columns`
--

DROP TABLE IF EXISTS `z_resources_columns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_resources_columns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) NOT NULL,
  `resourceid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `width` varchar(255) NOT NULL,
  `field` varchar(255) NOT NULL,
  `orderlink` int(1) NOT NULL DEFAULT '0',
  `template` text NOT NULL,
  `filter_query` varchar(255) NOT NULL,
  `filter_items` text NOT NULL,
  `eval` text NOT NULL,
  `escape` int(11) NOT NULL,
  `on_have_subcat` int(1) NOT NULL DEFAULT '1',
  `visible` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_resources_columns`
--

LOCK TABLES `z_resources_columns` WRITE;
/*!40000 ALTER TABLE `z_resources_columns` DISABLE KEYS */;
INSERT INTO `z_resources_columns` VALUES (6,6,21,'Название','','title',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (7,7,21,'Идентификатор','','sid',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (8,8,21,'Адрес','','url',0,'/page/{{sid}}','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (9,9,22,'Название','','title',1,'','title LIKE ?','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (10,10,22,'Идентификатор','','sid',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (11,11,57,'Название','','title',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (12,12,57,'Идентификатор','','sid',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (13,13,8,'Логин','50%','login',1,'','login LIKE ?','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (14,14,8,'Роль','','rolename',1,'','z_roles.id LIKE ?','$rolesModel = new Z_Model_Roles();\r\n\r\nreturn $rolesModel->fetchPairs();','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (15,15,11,'Роль','','title',1,'{{title}} ({{roleId}})','title LIKE ?','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (16,16,19,'Ресурс','50%','title',1,'','title LIKE ?','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (17,17,19,'Правило','','rule',0,'','rule = ?','return array(\'allow\'=>\'Разрешено\',\'deny\'=>\'Запрещено\');','return \"{{rule}}\"==\"allow\"?\"Разрешено\":\"Запрещено\";',0,1,1);
INSERT INTO `z_resources_columns` VALUES (18,18,18,'Роль','','title',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (19,19,13,'Привилегия','','title',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (20,20,32,'Адрес','','uri',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (21,21,32,'Заголовок','','title',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (23,22,62,'Название','','label',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (24,24,59,'Название','30%','title',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (25,32,59,'Поле','20%','field',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (26,33,59,'Ширина','','width',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (27,27,62,'Тип','','type',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (28,23,62,'Поле','','field',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (29,23,63,'Имя параметра','30%','title',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (30,24,63,'Значение','','value',0,'<pre>{{value}}</pre>','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (31,25,60,'Условие','50%','condition',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (32,26,60,'Значение','','value',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (33,27,61,'Модель','30%','model',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (34,28,61,'Условие','30%','condition',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (35,29,61,'Поля','','fields',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (36,30,64,'Поле','','field',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (37,31,64,'Модель','','model',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (38,34,65,'Название','','title',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (39,35,66,'Название таблицы','50%','title',0,'','title LIKE ?','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (40,36,67,'Поле','','title',0,'','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (41,37,67,'Тип','','type1',0,'{{type}} ({{len}})','','','',0,1,1);
INSERT INTO `z_resources_columns` VALUES (43,38,66,'Запустить конструктор','20%','run',0,'','','','if (in_array(\'{{title}}\',Z_Db_Table::getDefaultAdapter()->listTables()))\n	return \'<a href=\"\'.$this->url(array(\'action\'=>\'rebuild\',\'id\'=>{{id}})).\'\" class=\"z-ajax\">Модифицировать<a>\';\nelse\n	return \'<a href=\"\'.$this->url(array(\'action\'=>\'build\',\'id\'=>{{id}})).\'\" class=\"z-ajax\">Запустить конструктор<a>\';',0,1,1);
INSERT INTO `z_resources_columns` VALUES (45,39,67,'Индекс','','is_index1',0,'','','','return \'{{is_index}}\'?\'Да\':\'\';',0,1,1);
INSERT INTO `z_resources_columns` VALUES (46,40,75,'Заголовок','','title',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (47,41,75,'Ссылка','','url',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (48,42,77,'Название группы','20%','title',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (49,43,78,'Название','','title',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (50,44,79,'Название списка','20%','title',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (51,45,80,'Название','','title',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (52,46,81,'Название','20%','title',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (53,47,82,'Картинка','10%','pic_pv',0,'','','','return \'<img src=\"\'.$this->z_Preview({{pic}},array(\'h\'=>75)).\'\">\';',0,1,1);
INSERT INTO `z_resources_columns` VALUES (54,48,82,'Ссылка','','url',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (55,49,83,'Раздел','10%','title',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (56,50,84,'Название','','title',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (57,51,84,'Дата','','date',0,'','','','',1,1,1);
INSERT INTO `z_resources_columns` VALUES (59,52,78,'Дата добавления','','date',0,'','','','',1,1,1);
/*!40000 ALTER TABLE `z_resources_columns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_resources_conditions`
--

DROP TABLE IF EXISTS `z_resources_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_resources_conditions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resourceid` int(11) NOT NULL,
  `condition` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `recourceid` (`resourceid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_resources_conditions`
--

LOCK TABLES `z_resources_conditions` WRITE;
/*!40000 ALTER TABLE `z_resources_conditions` DISABLE KEYS */;
INSERT INTO `z_resources_conditions` VALUES (5,8,'login!=?','guest');
/*!40000 ALTER TABLE `z_resources_conditions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_resources_forms`
--

DROP TABLE IF EXISTS `z_resources_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_resources_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resourceid` int(11) NOT NULL,
  `orderid` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `field` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `required` int(1) NOT NULL DEFAULT '0',
  `value` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `only_for_root` int(1) NOT NULL DEFAULT '0',
  `show_check` text NOT NULL,
  `is_file` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `resourceid` (`resourceid`),
  KEY `orderid` (`orderid`)
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_resources_forms`
--

LOCK TABLES `z_resources_forms` WRITE;
/*!40000 ALTER TABLE `z_resources_forms` DISABLE KEYS */;
INSERT INTO `z_resources_forms` VALUES (1,21,1,'Text','title','Название',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (2,21,2,'text','sid','Идентификатор',1,'','',1,'',0);
INSERT INTO `z_resources_forms` VALUES (3,21,3,'mce','text','Текст',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (4,21,4,'checkbox','z_can_delete','Разрешить удалять?',0,'1','',1,'',0);
INSERT INTO `z_resources_forms` VALUES (5,22,5,'Text','title','Название',1,'','',1,'',0);
INSERT INTO `z_resources_forms` VALUES (6,22,6,'text','sid','Идентификатор',1,'','',1,'',0);
INSERT INTO `z_resources_forms` VALUES (7,22,7,'select','type','Тип',1,'','',1,'',0);
INSERT INTO `z_resources_forms` VALUES (8,57,8,'text','title','Название',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (9,57,9,'text','sid','Идентификатор',1,'','',1,'',0);
INSERT INTO `z_resources_forms` VALUES (10,57,10,'mce','description','Описание',1,'','',1,'',0);
INSERT INTO `z_resources_forms` VALUES (11,57,11,'mce','message','Шаблон сообщения',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (12,57,12,'text','theme','Тема',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (13,57,13,'text','from','От кого',0,'','Если не указано, то берется из настроек',0,'',0);
INSERT INTO `z_resources_forms` VALUES (14,57,14,'text','to','Кому',0,'','Если не указано, то берется из настроек',0,'',0);
INSERT INTO `z_resources_forms` VALUES (17,8,15,'text','login','Логин',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (18,8,16,'password','password','Пароль',0,'','Если не указывать, то он останется прежним',0,'',0);
INSERT INTO `z_resources_forms` VALUES (19,8,17,'select','role_id','Роль',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (20,11,18,'text','title','Название',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (21,11,19,'text','roleId','Идентификатор',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (22,19,20,'select','resource_id','Ресурс',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (23,19,21,'multiCheckbox','privileges','Привилегия',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (24,19,22,'select','rule','Правило',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (25,18,23,'select','parent_role_id','Родительская роль',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (26,13,24,'text','title','Название',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (27,13,25,'text','name','Идентификатор',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (28,32,26,'text','uri','Адрес',1,'','Адрес страницы относительно корня сайта. Должен начинаться с символа \"/\"',0,'',0);
INSERT INTO `z_resources_forms` VALUES (29,32,27,'text','title','Заголовок',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (30,32,28,'Checkbox','title_block','Добавлять к существующему заголовку',0,'1','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (31,32,29,'text','description','description',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (32,32,30,'Checkbox','description_block','Добавлять к существующему description',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (33,32,31,'text','keywords','keywords',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (34,32,32,'Checkbox','keywords_block','Добавлять к существующему keywords',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (35,11,33,'checkbox','z_can_delete','Возможно удалять?',0,'','',1,'',0);
INSERT INTO `z_resources_forms` VALUES (36,12,0,'text','title','Название',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (37,12,36,'text','resourceId','Идентификатор',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (38,12,37,'text','actionId','Действие',0,'list','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (39,12,38,'select','parentid','Родитель',1,'','',0,'return $this->_getParam(\'action\')==\'edit\';',0);
INSERT INTO `z_resources_forms` VALUES (40,12,39,'Checkbox','visible','Видимый в меню?',0,'1','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (41,12,41,'select','model','Модель',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (42,12,42,'Select','datatype','Тип',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (43,12,43,'text','default_field','Поле по умолчанию',0,'title','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (44,12,46,'text','order','Сортировка',0,'id','<p>Список полей для сортировки, разделяемый символом \";\"</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (45,12,47,'text','group','Группировка',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (46,12,48,'text','paginate','Постраничность',1,'15','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (47,12,49,'Checkbox','can_delete','Разрешить удалять?',0,'1','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (48,12,50,'Checkbox','can_edit','Разрешить редактировать?',0,'1','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (49,12,51,'Checkbox','can_add','Разрешить добавлять?',0,'1','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (50,12,52,'Checkbox','delete_confirm','Подтверждение удаления?',0,'1','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (51,12,53,'Checkbox','delete_on_have_child','Удалять при наличии детей?',0,'0','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (52,12,54,'Checkbox','sortable','Сортируемый?',0,'0','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (53,12,96,'Select','sortable_position','Позиция при добавлении',0,'','Работает только в сортируемых каталогах и списках',0,'',0);
INSERT INTO `z_resources_forms` VALUES (54,12,44,'text','parent_field','Родительское поле',0,'','Используется, если вы хотите установить связь с родительской таблицей. В этом случае родительской таблицей является таблица модели родительского ресурса.',0,'',0);
INSERT INTO `z_resources_forms` VALUES (55,62,57,'Select','type','Тип',1,'text','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (56,62,56,'text','field','Поле',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (57,62,55,'text','label','Название (label)',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (58,62,58,'Checkbox','required','Обязательное',0,'1','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (59,62,59,'text','value','Значение по умолчанию',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (60,62,60,'mce','description','Описание',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (61,62,62,'Checkbox','only_for_root','Видно только для суперпользователя',0,'0','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (62,62,94,'EditArea','show_check','Функция проверки доступности',0,'','<p>PHP функция. Если возвращает false, то поле будет недоступно.</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (63,59,63,'text','title','Название',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (64,59,64,'text','field','Поле в БД',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (65,59,65,'text','width','Ширина',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (66,59,68,'checkbox','orderlink','Использовать для сортировки',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (67,59,69,'text','template','Шаблон',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (68,59,70,'EditArea','eval','eval',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (69,59,95,'Text','filter_query','Фильтр',0,'','<p>Условие для SQL.</p>\n<p>Пример: \"id=?\" или \"title LIKE ?\"</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (70,59,97,'EditArea','filter_items','Элементы для фильтра с выбором',0,'','<p>Этот PHP код должен вернуть ассоцитиативный массив.</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (71,63,71,'text','title','Имя параметра',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (72,63,72,'EditArea','value','Значение',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (73,63,73,'checkbox','is_eval','PHP код',0,'1','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (74,60,74,'text','condition','Условие',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (75,60,75,'text','value','Значение',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (76,61,76,'select','model','Модель',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (77,61,77,'text','condition','Условие',1,'','<p>Условие присоединения в SQL винтаксисе.&nbsp;Возможно применение шаблона:</p>\n<p>{{table}} - таблица текущей модели</p>\n<p>{{jointable}} - таблица присоединяемой модели</p>\n<p>Пример: {{table}}.id={{jointable}}.tableid</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (78,61,78,'text','fields','Поля',1,'','<p>Блоки разделяются символом \";\"<br />Каждый блок содержит поле таблицы и имя этого поля в запросе<br />Например: title|mytitle;shop|shopname</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (79,64,79,'select','model','Модель',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (80,64,80,'text','field','Поле',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (81,64,81,'text','field1','Поле для связи на текущую таблицу',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (82,64,82,'text','field2','Поле для связи на таблицу указанной модели',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (83,65,83,'Text','title','Название',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (84,65,84,'EditArea','url','Ссылка',1,'','<p>php код, адрес ссылки</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (85,65,85,'Text','class','Класс',0,'','<p>класс тега в html коде</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (86,66,86,'Text','title','Название',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (87,67,87,'Text','title','Название',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (88,67,88,'Select','type','Тип',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (89,67,89,'Text','len','Длина',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (91,67,90,'Select','default','По умолчанию',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (92,67,91,'Text','default_value','Значение по умолчанию',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (93,67,92,'Checkbox','is_null','NULL',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (94,67,93,'Checkbox','is_index','Индекс',0,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (97,62,61,'Checkbox','is_file','Удалять файл',0,'1','<p>Действует только для типа \"Файл\". Если флажок установлен, то при удалении элемента, будет удален и сопутствующий файл.</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (98,59,98,'Checkbox','on_have_subcat','Показывать для разделов',1,'1','<p>Только для каталога.</p>\n<p>Если флажок установлен, то эта колонка будет показываться для всех разделов каталога.</p>\n<p>Если флажок снят, то колонка будет показываться только для \"листьев\" дерева каталога.</p>',0,'',1);
INSERT INTO `z_resources_forms` VALUES (99,12,40,'Checkbox','on_have_subcat','Показывать этот ресурс для всех разделов каталога',1,'1','<p>Если стоит галочка, то этот ресурс будет виден для всех разделов каталога.</p>\n<p>Иначе только для \"листьев\"</p>',0,'',1);
INSERT INTO `z_resources_forms` VALUES (100,59,66,'Checkbox','visible','Видимая',0,'1','<p>Если не установлен флажок, то колонка не будет видна в списке, но останется в фильтрах.</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (101,59,67,'Checkbox','escape','Escape',0,'1','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (105,12,45,'Text','indexate','Поля для индексирования',0,'','<p>список полей через точку с запятой</p>',0,'',0);
INSERT INTO `z_resources_forms` VALUES (106,75,99,'Text','title','Название пункта меню',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (107,75,100,'Select','page','Страница',0,'','',0,'if ($this->getRequest()->getActionName()==\'add\')\r\njQuery::evalScript(\'\r\n	$(\"#url\").attr(\"value\",$(\"#page\").attr(\"value\"));\r\n\');\r\njQuery::evalScript(\'\r\n	$(\"#page\").change(function(){\r\n		$(\"#url\").attr(\"value\",$(this).attr(\"value\"));\r\n	})\r\n\');\r\nreturn true;',0);
INSERT INTO `z_resources_forms` VALUES (108,75,101,'Text','url','Ссылка',1,'','',0,'',0);
INSERT INTO `z_resources_forms` VALUES (109,77,102,'Text','title','Название группы',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (110,77,103,'Mce','description','Краткое описание группы новостей',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (111,78,104,'Text','title','Название',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (112,78,105,'Date','date','Дата',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (113,78,106,'File','pic','Картинка новости',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (114,78,107,'Mce','description','Краткое описание',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (115,78,108,'Mce','text','Полный текст новости',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (116,79,109,'Text','title','Название списка',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (117,79,110,'Mce','description','Описание списка',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (118,80,111,'Text','title','Название',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (119,80,112,'File','pic','Изображение',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (120,80,113,'Text','url','Ссылка',0,'','<p>Если Вы хотите сделать ссылку на сторонний сайт, то следует начинать ее с http://</p>\r\n<p>например: http://google.ru</p>',0,'',1);
INSERT INTO `z_resources_forms` VALUES (121,80,114,'Mce','text','Текст',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (122,81,115,'Text','title','Название',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (123,81,120,'Mce','description','Описание',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (124,82,117,'File','pic','Картинка',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (125,82,118,'Text','description','Описание',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (126,82,119,'Text','url','Ссылка',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (127,81,116,'File','pic','Картинка',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (128,83,121,'Text','title','Название раздела',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (129,83,122,'Mce','description','Описание',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (130,84,123,'Text','title','Название',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (131,84,124,'Date','date','Дата',1,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (132,84,125,'Mce','description','Описание',0,'','',0,'',1);
INSERT INTO `z_resources_forms` VALUES (133,84,126,'Textarea','code','Код видеоплеера',1,'','',0,'',1);
/*!40000 ALTER TABLE `z_resources_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_resources_forms_params`
--

DROP TABLE IF EXISTS `z_resources_forms_params`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_resources_forms_params` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `is_eval` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `formid` (`formid`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_resources_forms_params`
--

LOCK TABLES `z_resources_forms_params` WRITE;
/*!40000 ALTER TABLE `z_resources_forms_params` DISABLE KEYS */;
INSERT INTO `z_resources_forms_params` VALUES (1,7,'multiOptions','return array(\r\n\'int\'		=>	\'Число\',\r\n\'bool\'		=>	\'Да/Нет\',\r\n\'string\'	=>	\'Строка\',\r\n\'password\'	=>	\'Пароль\',\r\n\'text\'		=>	\'Текст\',\r\n\'file\'		=>	\'Файл\',\r\n\'image\'		=>	\'Картинка\',\r\n\'html\'		=>	\'HTML текст\');',1);
INSERT INTO `z_resources_forms_params` VALUES (2,11,'description','$templateDescription = \'\';\r\nif (Z_Auth::getInstance()->getUser()->getRole()!=\'root\' && $this->_getParam(\'id\'))\r\n{\r\n  $templateDescription = $this->z_model->find($this->_getParam(\'id\'))->current()->description;\r\n}\r\nreturn $templateDescription;',1);
INSERT INTO `z_resources_forms_params` VALUES (3,19,'multiOptions','$rolesModel = new Z_Model_Roles();\r\nreturn $rolesModel->fetchPairs(array(\'id\',\'title\'),array(),\'title\');',1);
INSERT INTO `z_resources_forms_params` VALUES (4,17,'filters','return array(\'StringTrim\');',1);
INSERT INTO `z_resources_forms_params` VALUES (5,17,'validators','$exclude = NULL;\r\nif ($this->_getParam(\'action\')==\'edit\'){\r\n  $exclude = Z_Db_Table::getDefaultAdapter()->quoteInto(\'id != ?\', $this->_getParam(\'id\'));\r\n}\r\n\r\nreturn array(\r\n  array(\'StringLength\', true, array(4, 255)),\r\n  \'alnum\',\r\n  array(\'db_NoRecordExists\', true, array($this->z_model->info(\'name\'),\'login\',$exclude))\r\n);',1);
INSERT INTO `z_resources_forms_params` VALUES (6,18,'validators','return array(\r\n  array(\'StringLength\', true, array(6, 255)),\r\n);',1);
INSERT INTO `z_resources_forms_params` VALUES (7,21,'validators','$exclude = NULL;\r\nif ($this->_getParam(\'action\')==\'edit\')\r\n{\r\n  $exclude = Z_Db_Table::getDefaultAdapter()->quoteInto(\'id != ?\', $this->_getParam(\'id\'));\r\n}\r\nreturn array(\r\n array(\'db_NoRecordExists\', true, array($this->z_model->info(\'name\'),\'roleId\',$exclude))\r\n);',1);
INSERT INTO `z_resources_forms_params` VALUES (8,22,'MultiOptions','$resourcesModel = new Z_Model_Resources();\r\nreturn $resourcesModel->fetchPairsCat();',1);
INSERT INTO `z_resources_forms_params` VALUES (9,23,'MultiOptions','$privilegesModel = new Z_Model_Privileges();\r\nreturn $privilegesModel->fetchPairs(array(\'id\',\'title\'),array(),\'title asc\');',1);
INSERT INTO `z_resources_forms_params` VALUES (10,24,'MultiOptions','return array(\'allow\'=>\'Разрешить\',\'deny\'=>\'Запретить\');',1);
INSERT INTO `z_resources_forms_params` VALUES (11,25,'MultiOptions','$curRoleId = $this->_getParam($this->getResourceInfo()->resourceId.\'_parentid\');\r\n$rolesModel = new Z_Model_Roles();\r\nreturn $rolesModel->fetchPairs(array(\'id\',\'title\'),array(\'id!=?\'=>$curRoleId));',1);
INSERT INTO `z_resources_forms_params` VALUES (12,27,'validators','$exclude = NULL;\r\nif ($this->_getParam(\'action\')==\'edit\'){\r\n  $exclude = Z_Db_Table::getDefaultAdapter()->quoteInto(\'id != ?\', $this->_getParam(\'id\'));\r\n}\r\nreturn array(\r\n  array(\'db_NoRecordExists\', true, array($this->z_model->info(\'name\'),\'name\',$exclude))\r\n);',1);
INSERT INTO `z_resources_forms_params` VALUES (13,39,'MultiOptions','$options = $this->z_model->fetchPairsCat();\n$options[0] = \'Корень\';\nif ($this->_getParam(\'action\')==\'edit\') unset($options[$this->_getParam(\'id\')]);\nreturn $options;',1);
INSERT INTO `z_resources_forms_params` VALUES (14,41,'MultiOptions','$models = Z_Db_Model_Generator::getAllModels();\r\n$models = array_combine($models,$models);\r\n$models[\'\'] = \'Нет\';\r\nreturn $models;',1);
INSERT INTO `z_resources_forms_params` VALUES (15,42,'MultiOptions','return array(\'band\'=>\'Лента\',\'catalog\'=>\'Каталог\');',1);
INSERT INTO `z_resources_forms_params` VALUES (16,53,'MultiOptions','return array(\'bottom\'=>\'В конец\',\'top\'=>\'В начало\');',1);
INSERT INTO `z_resources_forms_params` VALUES (17,76,'MultiOptions','$models = Z_Db_Model_Generator::getAllModels();\nreturn array_combine($models,$models);\n',1);
INSERT INTO `z_resources_forms_params` VALUES (18,79,'MultiOptions','$models = Z_Db_Model_Generator::getAllModels();\nreturn array_combine($models,$models);',1);
INSERT INTO `z_resources_forms_params` VALUES (23,55,'MultiOptions','return array(\r\n	\'Text\'		=>	\'Строка\',\r\n	\'Autocomplete\'	=>	\'Автодополнение\',\r\n	\'AutocompleteId\'=>	\'Автодополнение с выбором идентификатора\',\r\n	\'Date\'		=>	\'Дата\',\r\n	\'Textarea\'	=>	\'Текст\',\r\n	\'Mce\'		=>	\'HTML редактор\',\r\n	\'File\'		=>	\'Файл\',\r\n	\'Select\'	=>	\'Выпадающий список\',\r\n	\'Radio\'		=>	\'Радио-кнопка\',\r\n	\'Checkbox\'	=>	\'Флажок\',\r\n	\'MultiCheckbox\'	=>	\'Мультифлажок\',\r\n	\'EditArea\'	=>	\'Редактор кода\',\r\n	\'Hidden\'	=>	\'Скрытое поле\',\r\n	\'Password\'	=>	\'Пароль\',\r\n	\'PointPicker\'	=>	\'Выбор точки на картинке\'\r\n);',1);
INSERT INTO `z_resources_forms_params` VALUES (24,88,'MultiOptions','return array(\n	\'int\'		=>	\'int\',\n	\'varchar\'	=>	\'varchar\',\n	\'text\'		=>	\'text\',\n	\'date\'		=>	\'date\',\n	\'timestamp\'	=>	\'timestamp\'\n);',1);
INSERT INTO `z_resources_forms_params` VALUES (25,89,'validators','return array(\n	array(\'Digits\')\n);',1);
INSERT INTO `z_resources_forms_params` VALUES (26,91,'MultiOptions','return array(\n	\'no\'			=>	\'Нет\',\n	\'asdefine\'		=>	\'Как определено\',\n	\'CURRENT_TIMESTAMP\'	=>	\'CURRENT_TIMESTAMP\'\n);',1);
INSERT INTO `z_resources_forms_params` VALUES (27,3,'filemanager','1',0);
INSERT INTO `z_resources_forms_params` VALUES (28,107,'multiOptions','return Z_Resource_Aggregator::getInstance()->getList();',1);
INSERT INTO `z_resources_forms_params` VALUES (29,107,'size','15',0);
/*!40000 ALTER TABLE `z_resources_forms_params` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_resources_joins`
--

DROP TABLE IF EXISTS `z_resources_joins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_resources_joins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) NOT NULL,
  `resourceid` int(11) NOT NULL,
  `model` varchar(255) NOT NULL,
  `condition` varchar(255) NOT NULL,
  `fields` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`),
  KEY `resourceid` (`resourceid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_resources_joins`
--

LOCK TABLES `z_resources_joins` WRITE;
/*!40000 ALTER TABLE `z_resources_joins` DISABLE KEYS */;
INSERT INTO `z_resources_joins` VALUES (2,2,8,'Z_Model_Roles','{{table}}.role_id={{jointable}}.id','title|rolename');
INSERT INTO `z_resources_joins` VALUES (3,3,19,'Z_Model_Resources','{{table}}.resource_id={{jointable}}.id','title|title');
INSERT INTO `z_resources_joins` VALUES (4,4,18,'Z_Model_Roles','{{table}}.parent_role_id={{jointable}}.id','title|title');
/*!40000 ALTER TABLE `z_resources_joins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_resources_refers`
--

DROP TABLE IF EXISTS `z_resources_refers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_resources_refers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resourceid` int(11) NOT NULL,
  `field` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `field1` varchar(255) NOT NULL,
  `field2` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `resourceid` (`resourceid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_resources_refers`
--

LOCK TABLES `z_resources_refers` WRITE;
/*!40000 ALTER TABLE `z_resources_refers` DISABLE KEYS */;
INSERT INTO `z_resources_refers` VALUES (1,19,'privileges','Z_Model_Privileges_Connect','rule_id','privilege_id');
/*!40000 ALTER TABLE `z_resources_refers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_roles`
--

DROP TABLE IF EXISTS `z_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roleId` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `z_can_delete` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_roles`
--

LOCK TABLES `z_roles` WRITE;
/*!40000 ALTER TABLE `z_roles` DISABLE KEYS */;
INSERT INTO `z_roles` VALUES (1,'guest','Посетитель сайта',0);
INSERT INTO `z_roles` VALUES (2,'root','Суперпользователь',0);
INSERT INTO `z_roles` VALUES (3,'admin','Администратор сайта',0);
INSERT INTO `z_roles` VALUES (4,'seo_role','SEO Специалист',0);
/*!40000 ALTER TABLE `z_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_roles_connect`
--

DROP TABLE IF EXISTS `z_roles_connect`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_roles_connect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `child_role_id` int(11) NOT NULL,
  `parent_role_id` int(11) NOT NULL,
  `orderid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_child_role_id` (`child_role_id`),
  KEY `fk_parent_role_id` (`parent_role_id`),
  KEY `orderid` (`orderid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_roles_connect`
--

LOCK TABLES `z_roles_connect` WRITE;
/*!40000 ALTER TABLE `z_roles_connect` DISABLE KEYS */;
/*!40000 ALTER TABLE `z_roles_connect` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_rules`
--

DROP TABLE IF EXISTS `z_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `rule` enum('allow','deny') DEFAULT 'deny',
  PRIMARY KEY (`id`),
  KEY `fk_roleId` (`role_id`),
  KEY `fk_resourceId` (`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_rules`
--

LOCK TABLES `z_rules` WRITE;
/*!40000 ALTER TABLE `z_rules` DISABLE KEYS */;
INSERT INTO `z_rules` VALUES (29,2,50,'allow');
INSERT INTO `z_rules` VALUES (38,3,11,'deny');
INSERT INTO `z_rules` VALUES (39,3,13,'deny');
INSERT INTO `z_rules` VALUES (41,3,24,'allow');
INSERT INTO `z_rules` VALUES (42,3,53,'allow');
INSERT INTO `z_rules` VALUES (43,3,22,'deny');
INSERT INTO `z_rules` VALUES (44,3,31,'allow');
INSERT INTO `z_rules` VALUES (45,3,57,'deny');
INSERT INTO `z_rules` VALUES (46,3,74,'allow');
INSERT INTO `z_rules` VALUES (47,3,32,'allow');
INSERT INTO `z_rules` VALUES (48,4,32,'allow');
/*!40000 ALTER TABLE `z_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_statpages`
--

DROP TABLE IF EXISTS `z_statpages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_statpages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` varchar(255) NOT NULL,
  `crated_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `title` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `z_can_delete` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sid` (`sid`),
  KEY `crated_on` (`crated_on`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_statpages`
--

LOCK TABLES `z_statpages` WRITE;
/*!40000 ALTER TABLE `z_statpages` DISABLE KEYS */;
INSERT INTO `z_statpages` VALUES (6,'index','2010-05-31 05:40:46','Возможности и преимущества zcmf','<ul>\r\n<li>Быстрое создание админки сайта любой сложности.</li>\r\n<li>Возможно использовать абсолютно любые шаблоны для сайта! Никаких специфических требований к верстке. Полная свобода для дизайнера и верстальщика!</li>\r\n<li>Использование Zend Framework дает широкие возможности по расширению функционала сайта.</li>\r\n<li>Только человекопонятные URL (ЧПУ)!</li>\r\n<li>Встроенная система авторизации и распределения прав доступа.</li>\r\n<li>Встроенные инструменты для работы с изображениями (генерация превью, наложение водных знаков, использование фильтров).</li>\r\n<li>Удобная в использовании система кэширования.</li>\r\n<li>Уже есть все необходимые для SEO инструменты!</li>\r\n<li>Генерация кода для создания моделей и контроллеров админки.</li>\r\n<li>Единое хранилище загружаемых файлов.</li>\r\n<li>Интегрированный инструменты для реализации поиска на основе технологии Lucene.</li>\r\n</ul>',0);
/*!40000 ALTER TABLE `z_statpages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_structure`
--

DROP TABLE IF EXISTS `z_structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) DEFAULT NULL,
  `parentid` int(11) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `uri` varchar(255) DEFAULT NULL,
  `module` varchar(255) DEFAULT NULL,
  `controller` varchar(255) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `params` varchar(255) DEFAULT NULL,
  `visible` int(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_structure`
--

LOCK TABLES `z_structure` WRITE;
/*!40000 ALTER TABLE `z_structure` DISABLE KEYS */;
/*!40000 ALTER TABLE `z_structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_titles`
--

DROP TABLE IF EXISTS `z_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_titles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) NOT NULL,
  `uri` varchar(1024) NOT NULL,
  `title` varchar(1024) NOT NULL,
  `title_block` int(1) DEFAULT '0',
  `description` varchar(1024) DEFAULT NULL,
  `description_block` int(1) DEFAULT '0',
  `keywords` varchar(1024) DEFAULT NULL,
  `keywords_block` int(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_titles`
--

LOCK TABLES `z_titles` WRITE;
/*!40000 ALTER TABLE `z_titles` DISABLE KEYS */;
INSERT INTO `z_titles` VALUES (1,1,'/','ZCMF Сайт-Визитка',0,'Сайт разработан на php фреймворке ZCMF',0,'ZCMF, PHP, Zend Framework',0);
/*!40000 ALTER TABLE `z_titles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_upload`
--

DROP TABLE IF EXISTS `z_upload`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_upload` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_upload`
--

LOCK TABLES `z_upload` WRITE;
/*!40000 ALTER TABLE `z_upload` DISABLE KEYS */;
INSERT INTO `z_upload` VALUES (9,2,'zagruzhennoe_(4).png','0cnKEtd7','zagruzhennoe__4_.png',0);
INSERT INTO `z_upload` VALUES (12,2,'logo.png','V7mxfDsU','logo.png',0);
INSERT INTO `z_upload` VALUES (22,3,'winter_wallpapers_40.jpg','Rtxnb6B5','winter_wallpapers_40.jpg',0);
INSERT INTO `z_upload` VALUES (23,3,'winter_wallpapers_17.jpg','dRU9o62G','winter_wallpapers_17.jpg',0);
INSERT INTO `z_upload` VALUES (30,2,'p.png','cCkSbFpB','p.png',0);
INSERT INTO `z_upload` VALUES (31,2,'f.jpg','ASm8V7zl','f.jpg',0);
/*!40000 ALTER TABLE `z_upload` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `z_users`
--

DROP TABLE IF EXISTS `z_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `z_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `z_can_delete` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_z_users_z_roles1` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `z_users`
--

LOCK TABLES `z_users` WRITE;
/*!40000 ALTER TABLE `z_users` DISABLE KEYS */;
INSERT INTO `z_users` VALUES (1,'guest','7b9f4e78ce9b50235cf35aee4372062e:iJb5tIu9HUA2XPoFl1BTx4vV6egsjz3G',1,0);
INSERT INTO `z_users` VALUES (2,'root','e3ddfec6c944fa1388b213a338d7e4aa:3OsnTjzMt7EKxHVAJgd0bG9L45FXprlo',2,0);
INSERT INTO `z_users` VALUES (3,'admin','8b68c3c2e86d46a985204c2ba9bedd52:T1XUj489LeES6rz0G5onbyVd3vlmRBHJ',3,0);
/*!40000 ALTER TABLE `z_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2011-05-02 15:25:06
