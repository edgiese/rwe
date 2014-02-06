--crud queries for product info module

-----------------------------------------------------manufacturer table queries
--:addmfr:C creates a new entry for a manufacturer
-->name:4
INSERT INTO prod_mfr (name) VALUES (:name)

--:delmfr:D deletes a mfr by name
-->mfr:2
DELETE FROM prod_mfr WHERE id=:mfr;
UPDATE prod_info SET mfr=0 WHERE mfr=:mfr 

--:allMfrs:R return ids and names for all manufacturers
--<id:2,name:4
SELECT id,name FROM prod_mfr ORDER BY name

--:getMfrByName:R gets an id for a single manufacturer, looking by name
-->mfr:4
--<id:2
SELECT id FROM prod_mfr WHERE name=:mfr

--:getMfrById:R gets an id for a single manufacturer, looking by id
-->mfr:2
--<id:2
SELECT id FROM prod_mfr WHERE id=:mfr

--:getMfr:R gets an id and a name for a single manufacturer, looking by id
-->mfr:2
--<name:4,id:2
SELECT name,id FROM prod_mfr WHERE id=:mfr

--:idsbymfr:R gets ids having a particular manufacturer
-->mfr:2
--<id:2
SELECT id FROM  prod_info WHERE mfr=:mfr

--------------------------------------------------------

--:new:C inserts a new prod description record
-->id:2
INSERT INTO prod_info (id,mfr,title,mainimg,description,shape,dim1,dim2,dim3,weight,flags) VALUES (:id,0,0,0,0,'none',0,0,0,0,0)

--:delete:D removes a prod description record (warning:  no resource deletes)
-->id:2
DELETE FROM prod_info WHERE id=:id

--:info:R reads a product description record
-->id:2
--<idmfr:2,idtitle:4,idimg:2,iddesc:4,shape:4,dim1:2,dim2:2,dim3:2,weight:2,flags:2|1
SELECT mfr,title,mainimg,description,shape,dim1,dim2,dim3,weight,flags FROM prod_info WHERE id=:id

--:setmfr:D sets a prod to have a mfr
-->id:2,idmfr:2
UPDATE prod_info SET mfr=:idmfr WHERE id=:id

--:settitle:D sets a prod to have a title
-->id:2,idtitle:4
UPDATE prod_info SET title=:idtitle WHERE id=:id

--:setimg:D sets a prod to have an image id
-->id:2,idimage:2
UPDATE prod_info SET mainimg=:idimage WHERE id=:id

--:setdesc:D sets a prod to have an image id
-->id:2,iddesc:4
UPDATE prod_info SET description=:iddesc WHERE id=:id

--:setshape:D sets a prod to have a shape
-->id:2,shape:4,dim1:2,dim2:2,dim3:2
UPDATE prod_info SET shape=:shape,dim1=:dim1,dim2=:dim2,dim3=:dim3 WHERE id=:id

--:setweight:D sets a prod to have a weight
-->id:2,weight:2
UPDATE prod_info SET weight=:weight WHERE id=:id

--:setflags:D sets a prod's flags
-->id:2,flags:2
UPDATE prod_info SET flags=:flags WHERE id=:id

--:prodidfromtitle:R gets a product's id from the id of the title in the description
-->textid:4
--<id:2|1
SELECT id FROM prod_info WHERE title=:textid

--:prodidfromdesc:R gets a product's id from the id of the title in the description
-->textid:4
--<id:2|1
SELECT id FROM prod_info WHERE description=:textid

------------------------------------------------------ Group Table Queries
--:addgroup:C creates a new group
-->category:2,description:4
INSERT INTO prod_groupdesc (description) VALUES (:description)

--:findgroup:R gets a group id from its description
-->description:4
--<id:2|1
SELECT groupid FROM prod_groupdesc WHERE description=:description

--:allgroups:R return ids and descriptions for all groups
--<groupid:2,description:4
SELECT groupid,description FROM prod_groupdesc ORDER BY description

--:categorygroups:R return ids and descriptions for all groups in a category
-->category:2
--<groupid:2,description:4
SELECT groupid,description FROM prod_groupdesc WHERE category=:category ORDER BY description

--:groupinfo:R returns description and count of items for a group
-->groupid:2
--<description:4,count:2
SELECT description,COUNT(itemid) FROM prod_groupdesc INNER JOIN prod_groups ON prod_groupdesc.groupid=prod_groups.groupid GROUP BY prod_groupdesc.groupid HAVING prod_groupdesc.groupid=:groupid

--:renamegroup:D update a description
-->groupid:2,description:4
UPDATE prod_groupdesc SET description=:description WHERE groupid=:groupid

--:deletegroup:D deletes a group description record
-->groupid:2
DELETE FROM prod_groupdesc WHERE groupid=:groupid

--:addgroupitem:D adds an item into a group
-->groupid:2,itemid:2
INSERT INTO prod_groups (groupid,itemid) VALUES (:groupid,:itemid)

--:removegroupitem:D removes an item from a group
-->groupid:2,itemid:2
DELETE FROM prod_groups WHERE groupid=:groupid AND itemid=:itemid

--:emptygroup:D removes all items from a group
-->groupid:2
DELETE FROM prod_groups WHERE groupid=:groupid

--:owninggroups:R return all group ids that contain an item id
-->itemid:2
--<groupid:2
SELECT groupid FROM prod_groups WHERE itemid=:itemid

--:firstowninggroup:R returns first name of group owning of a particular category
-->itemid:2,category:2
--<group:4|1
SELECT description FROM prod_groupdesc INNER JOIN prod_groups ON prod_groupdesc.groupid=prod_groups.groupid WHERE prod_groups.itemid=:itemid AND category=:category LIMIT 1

--:groupitems:R return all items in group
-->groupid:2
--<itemid:2
SELECT itemid FROM prod_groups WHERE groupid=:groupid

--:ungroupeditems:R returns all ids that are not in groups
--<itemid:2
SELECT id FROM prod_main LEFT JOIN prod_groups ON id=itemid WHERE itemid IS NULL

--:isitemingroup:R returns 1 if item is in a group or zero otherwise
-->groupid:2,itemid:2
--<ingroup:2
SELECT COUNT(itemid) FROM prod_groups WHERE groupid=:groupid AND itemid=:itemid
