--:idfromlongname:R gets id from a longname
-->longname:4
--<id:4
SELECT textid FROM _text WHERE longname=:longname

--:insert:C inserts a new text object
-->iscreole:1,name:4,inittext:4
INSERT INTO _text (iscreole,tag,text) VALUES (:iscreole,:name,:inittext)

--:getAllTextIds:R returns list of all text ids
--<textid:4
SELECT textid FROM _text 

--:getfromid:R gets info about a text object from its id
-->textid:4
--<iscreole:1,text:4
SELECT iscreole,text FROM _text WHERE textid=:textid

--:getfromname:R gets info about a text object from its id
-->name:4
--<textid:4
SELECT textid FROM _text WHERE tag=:name

--:updatetext:D updates text for a particular id
-->textid:4,text:4
UPDATE _text SET text=:text,lastedit=CURRENT_TIMESTAMP WHERE textid=:textid

--:delete:D deletes a text id
-->id:4
DELETE FROM _text WHERE textid=:id
