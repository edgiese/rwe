--:idfromname:R gets scrapbook id from scrapbook name
-->name:4
--<id:2|1
SELECT idscrapbook FROM scrapbook_main WHERE description=:name 

--:groupnames:R gets all journals for a scrapbook
-->id:2
--<idgroup:2,groupname:4
SELECT idgroup,idjournal FROM scrapbook_group WHERE idscrapbook=:id ORDER BY submitdate DESC 

--:groupjournal:R gets a journal id for a group
-->id:2
--<idjournal:2|1
SELECT idjournal FROM scrapbook_group WHERE idgroup=:id 

--:uncategorized:R gets all uncategorized image data for a scrapbook
-->id:2,id2:2
--<image:2,submittedby:4,submitdate:5,comment:4,source:4
SELECT idimage,submittedby,submitdate,comment,source FROM scrapbook_image 
    WHERE idscrapbook=:id 
	AND NOT EXISTS (SELECT * FROM scrapbook_toc WHERE idscrapbook=:id2 AND scrapbook_toc.idimage=scrapbook_image.identry) 

--:groupimages:R gets all image data for a group
-->id:2
--<image:2,submittedby:4,submitdate:5,comment:4,source:4
SELECT scrapbook_image.idimage,submittedby,submitdate,comment,source FROM scrapbook_image 
    INNER JOIN scrapbook_toc ON identry=scrapbook_toc.idimage 
	WHERE idgroup=:id ORDER BY tocpos 

--:imageids:R gets all the ids of images for a scrapbook entry
-->id:2
--<idimage:4
SELECT idimage FROM scrapbook_image WHERE idscrapbook=:id

--:deleteimages:D deletes all images for a scrapbook
-->id:2
DELETE FROM scrapbook_image WHERE idscrapbook=:id
 
--:deletegroups:D deletes all groups for a scrapbook
-->id:2
DELETE FROM scrapbook_group,scrapbook_toc USING scrapbook_group LEFT JOIN scrapbook_toc ON scrapbook_group.idgroup=scrapbook_toc.idgroup WHERE idscrapbook=:id

--:delete:D deletes a scrapbook description record
-->id:2
DELETE FROM scrapbook_main WHERE idscrapbook=:id

--:create:C creates a new scrapbook from a description
-->description:4
INSERT INTO scrapbook_main (description) VALUES (:description)

--:newgroup:C creates a new group for a scrapbook
-->idscrapbook:2,idjournal:4
INSERT INTO scrapbook_group (idscrapbook,idjournal) VALUES (:idscrapbook,:idjournal)

--:newimage:C creates a new image for a scrapbook
-->id:2,idimage:4,submittedby:4,comment:4,source:4
INSERT INTO scrapbook_image (idscrapbook,idimage,submittedby,comment,source) VALUES (:id,:idimage,:submittedby,:comment,:source)

--:countseq:R return count of sequence numbers for a given group
-->idgroup:2
--<count:2|1
SELECT COUNT(*) FROM scrapbook_toc WHERE idgroup=:idgroup

--:maxseq:R return max of sequence numbers for a given group
-->idgroup:2
--<max:2|1
SELECT MAX(tocpos) FROM scrapbook_toc WHERE idgroup=:idgroup

--:toc:C creates a new table of contents entry for a scrapbook
-->idgroup:2,idimage:4,tocpos:2
INSERT INTO scrapbook_toc (idgroup,idimage,tocpos) VALUES (:idgroup,:idimage,:tocpos)

