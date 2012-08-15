--:clearmst:D clears the mst table
TRUNCATE TABLE _mst

--:mst:C inserts a master style table record
-->page:4,stype:2,name:4,html:4,parent:4,keyword:4,pseudoclass:4,value:4
INSERT INTO _mst (page,stype,name,html,parent,keyword,pseudoclass,assignment) VALUES (:page,:stype,:name,:html,:parent,:keyword,:pseudoclass,:value)

--:getstyles:R dumps out the styles as formatted
--<stype:2,page:4,name:4,html:4,parent:4,keyword:4,pseudoclass:4,value:4
SELECT DISTINCT stype,page,name,html,parent,keyword,pseudoclass,assignment FROM `_mst` order by keyword,assignment,html,stype,name

--:appendimportant:D appends the word !important to all class-based style values
UPDATE _mst SET assignment=CONCAT(assignment," !important") WHERE stype=5 OR stype=6



--:defaultkwvals:R reads mst table showing most common value assignments first in group
--<keyword:4,value:4,count:2
SELECT keyword,assignment,COUNT(assignment) AS freq FROM `_mst` GROUP BY keyword,assignment ORDER BY keyword,freq DESC

--:setdefaultkw:D sets one record to mark a default value
-->keyword:4,value:4
UPDATE _mst SET longname='',shortname='',page='',html='*' WHERE keyword=:keyword AND assignment=:value AND LEFT(longname,1) <> '.' LIMIT 1

--:cleanupdefaultkw:D deletes all redundant default-value records
-->keyword:4,value:4
DELETE FROM _mst WHERE keyword=:keyword AND assignment=:value AND html <> '*' AND LEFT(longname,1) <> '.' 

--:defaulthtmlvals:R reads in html assignments to see if we will override global default
-->keyword:4
--<html:4,value:4,count:2
SELECT html,assignment,count(assignment) as freq FROM `_mst` WHERE keyword=:keyword group by html,assignment order by html,freq desc

--:setdefaulthtml:D sets one record to mark a default value
-->html:4,keyword:4,value:4
UPDATE _mst SET longname='',shortname='',page='' WHERE keyword=:keyword AND assignment=:value AND html=:html AND LEFT(longname,1) <> '.' LIMIT 1

--:cleanupdefaulthtml:D deletes all redundant html default-value records
-->html:4,keyword:4,value:4
DELETE FROM _mst WHERE keyword=:keyword AND assignment=:value AND html = :html AND longname <> '' AND LEFT(longname,1) <> '.' 

--:dupshort:R looks for shortnames that can be conflated by a longname
--<count:2,longname:4,html:4,keyword:4,value:4
SELECT COUNT(longname) AS freq,longname,html,keyword,assignment FROM `_mst` GROUP BY longname,html,keyword,assignment HAVING longname <> '' AND LEFT(longname,1) <> '.'

--:updateshort:D updates a shortname with a new short name
-->short:4,newshort:4
UPDATE _mst SET shortname=:newshort WHERE shortname=:short AND LEFT(longname,1) <> '.'

--:dupshortlist:R gets info about shortnames associated with a longname
-->longname:4
--<page:4,short:4
SELECT DISTINCT page,shortname FROM _mst WHERE longname=:longname

--:concatpseudoclass:D concatenates pseudoclass onto html
UPDATE _mst SET html=CONCAT_WS(":",html,pseudoclass) WHERE pseudoclass <> ''

--:getstyles:R dumps out the styles as formatted
--<longname:4,page:4,shortname:4,html:4,keyword:4,value:4,contained:2
SELECT DISTINCT longname,page,shortname,html,keyword,assignment,contained FROM `_mst` order by keyword,assignment,html,shortname



