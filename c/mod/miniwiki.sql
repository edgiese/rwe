--crud queries for miniwiki module

--:countpages:R return count of records for a given name -- should always be 0 or 1
-->name:4
--<count:2|1
SELECT COUNT(*) FROM miniwiki_main WHERE name=:name

--:create:C creates a new action from a description
-->name:4,textid:4
INSERT INTO miniwiki_main (name,textid) VALUES (:name,:textid)

--:allRows:R gets names of all miniwiki pages
--<name:4
SELECT name FROM miniwiki_main ORDER BY name

--:getTextId:R gets a text id for name -- only 1 allowed
-->name:4
--<textid:4|1
SELECT textid FROM miniwiki_main WHERE name=:name
