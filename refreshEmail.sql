-- DO NOT RUN BLINDLY, CREATED FOR A SPECIFIC SITUATION
-- This script can regenrate the emails for CiviCRM to process
-- In the case this script was create, CiviCRM was ignoring all emails because the headers from Mailgun call backs were not being decoded properly.
-- @author: David Hayes <david@blackbrick.software>

-- Preview!
SELECT
  m.id,
  m.recipient,
  CONCAT(
    "From: <postmaster@local>", CHAR(13), CHAR(10),
    "Return-Path: <>", CHAR(13), CHAR(10),
    "X-Civimail-Bounce: ", IFNULL(x.headerValue, ''), CHAR(13), CHAR(10),
    "Delivered-To: ", IFNULL(x.headerValue, ''), CHAR(13), CHAR(10),
    "To: ", IFNULL(x.headerValue, ''), CHAR(13), CHAR(10),
    "Received: ", IFNULL(r.headerValue, ''), CHAR(13), CHAR(10),
    "Date: ", IFNULL(d.headerValue, ''), CHAR(13), CHAR(10),
    "Subject: ", IFNULL(s.headerValue, ''), CHAR(13), CHAR(10),
    CHAR(13), CHAR(10),
    @description:=CAST(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(m.post_data,'$."description"')), '') AS CHAR CHARACTER SET utf8), CHAR(13), CHAR(10),
    IF(@description='Not delivering to previously bounced address', CONCAT("RecipNotFound", CHAR(13), CHAR(10)), '') -- guarentee a bounce categorization by civicrm
  )
FROM mailgun_events m
LEFT JOIN mailgun_events_headers x
  ON m.id = x.mailgun_events_id AND x.headerName = 'X-Civimail-Bounce'
LEFT JOIN mailgun_events_headers r
  ON m.id = r.mailgun_events_id AND r.headerName = 'Received'
LEFT JOIN mailgun_events_headers d
  ON m.id = d.mailgun_events_id AND d.headerName = 'Date'
LEFT JOIN mailgun_events_headers s
  ON m.id = s.mailgun_events_id AND s.headerName = 'Subject'
WHERE 1
  AND m.reason='hardfail'
  AND m.id IN (
    SELECT MIN(id)
    FROM mailgun_events
    WHERE post_data LIKE '%X-Civimail-Bounce%'
    GROUP BY recipient
  )


-- Regenerate emails

-- create backup
DROP TABLE IF EXISTS mailgun_events2;
CREATE TABLE mailgun_events2 
SELECT *
FROM mailgun_events;

-- make a table to store headers
DROP TABLE IF EXISTS mailgun_events_headers;
CREATE TABLE IF NOT EXISTS mailgun_events_headers (
  id INT(11) NOT NULL AUTO_INCREMENT,
  mailgun_events_id INT(11) NOT NULL,
  rawHeader MEDIUMTEXT,
  headerName VARCHAR(255),
  headerValue MEDIUMTEXT,
  PRIMARY KEY (id),
  KEY headerName (headerName),
  CONSTRAINT `FK_mailgun_events_id` FOREIGN KEY (mailgun_events_id) REFERENCES mailgun_events(id) ON DELETE CASCADE
);

-- populate header table
DROP FUNCTION IF EXISTS populate_headers;
DELIMITER $$
CREATE FUNCTION populate_headers(
  mailgunEventsId INT(11),
  postData MEDIUMTEXT
)
RETURNS INT(10)
DETERMINISTIC
BEGIN

  SET @mailgun_events_id = mailgunEventsId;
  SET @post_data = postData;
  
  SET @messageHeaders = JSON_UNQUOTE(JSON_UNQUOTE(JSON_EXTRACT(@post_data,'$."message-headers"')));
  SET @length = JSON_LENGTH(@messageHeaders);
  SET @indx = 0;
  
  REPEAT
    
    SET @rawHeader = JSON_EXTRACT(@messageHeaders, CONCAT("$[", @indx, "]"));
    SET @headerName = JSON_UNQUOTE(JSON_EXTRACT(@rawHeader, "$[0]"));
    SET @headerValue = JSON_UNQUOTE(JSON_EXTRACT(@rawHeader, "$[1]"));

    INSERT INTO mailgun_events_headers
    (
      mailgun_events_id,
      rawHeader,
      headerName,
      headerValue
    )
    VALUES
    (
      @mailgun_events_id,
      @rawHeader,
      @headerName,
      @headerValue
    );
  
    SET @indx = @indx + 1;

    UNTIL @indx = @length
  END REPEAT;

  RETURN @indx;
END
$$
DELIMITER ;

-- populate headers table for some records
-- numbers get out of control if you do all of them
SELECT populate_headers(m.id, m.post_data)
FROM mailgun_events m
WHERE 1
  AND m.reason='hardfail'
  AND m.id IN (
    SELECT MIN(id)
    FROM mailgun_events
    WHERE post_data LIKE '%X-Civimail-Bounce%'
    GROUP BY recipient
  )
LIMIT 999999; -- php my admin auto adds limits

-- populate mailgun emails
UPDATE mailgun_events m
LEFT JOIN mailgun_events_headers x
  ON m.id = x.mailgun_events_id AND x.headerName = 'X-Civimail-Bounce'
LEFT JOIN mailgun_events_headers r
  ON m.id = r.mailgun_events_id AND r.headerName = 'Received'
LEFT JOIN mailgun_events_headers d
  ON m.id = d.mailgun_events_id AND d.headerName = 'Date'
LEFT JOIN mailgun_events_headers s
  ON m.id = s.mailgun_events_id AND s.headerName = 'Subject'
SET 
  email = CONCAT(
    "From: <postmaster@local>", CHAR(13), CHAR(10),
    "Return-Path: <>", CHAR(13), CHAR(10),
    "X-Civimail-Bounce: ", IFNULL(x.headerValue, ''), CHAR(13), CHAR(10),
    "Delivered-To: ", IFNULL(x.headerValue, ''), CHAR(13), CHAR(10),
    "To: ", IFNULL(x.headerValue, ''), CHAR(13), CHAR(10),
    "Received: ", IFNULL(r.headerValue, ''), CHAR(13), CHAR(10),
    "Date: ", IFNULL(d.headerValue, ''), CHAR(13), CHAR(10),
    "Subject: ", IFNULL(s.headerValue, ''), CHAR(13), CHAR(10),
    CHAR(13), CHAR(10),
    @description:=CAST(IFNULL(JSON_UNQUOTE(JSON_EXTRACT(m.post_data,'$."description"')), '') AS CHAR CHARACTER SET utf8), CHAR(13), CHAR(10),
    IF(@description='Not delivering to previously bounced address', CONCAT("RecipNotFound", CHAR(13), CHAR(10)), '') -- guarentee a bounce categorization by civicrm
  ),
  ignored = 0,
  processed = 0
WHERE 1
  AND m.reason='hardfail'
  AND m.id IN (
    SELECT min_id FROM (
      SELECT MIN(id) AS min_id
      FROM mailgun_events
      WHERE post_data LIKE '%X-Civimail-Bounce%'
      GROUP BY recipient
    ) AS t
  )

-- clean up our mess
DROP FUNCTION IF EXISTS populate_headers;
DROP TABLE IF EXISTS mailgun_events_headers;
DROP TABLE IF EXISTS mailgun_events2;