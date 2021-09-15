# NiceHash-MiningAnalyse
Simple interface for analyse NiceHash account mining statistics in private database/server.


[![Build Status](https://travis-ci.org/joemccann/dillinger.svg?branch=master)](https://core.telegram.org/bots/api)

![alt text](https://github.com/JavohirSD/NiceHash-MiningAnalyse/blob/main/demo.png?raw=true)

### Installation

1. Upload index.php to your Apache or Ngnix web server
2. Create table and edit database configurations.
3. Create API keys (https://www.nicehash.com/my/settings/keys)
4. Enjoy !

### Minimal database structure
You can modify database by adding more columns like a 'woker_id', 'speedAccepted', 'rig_id' for future deep analyses and optimisations

```sql
CREATE TABLE `history` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`profit`  VARCHAR(255) NOT NULL,
	`power`   VARCHAR(255) NOT NULL,
	`created_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `query_id` (`query_id`)
)

COLLATE='utf8_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=1;
```

### Author
  - ###### Javohir Abdirasulov
   -  alienware7x@gmail.com
   -  Telegram.me/JavohirSD

License
----

MIT

**Free Software, Hell Yeah!**
