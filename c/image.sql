--:info:R gets image info from an image id
-->id:4
--<imageid:4,filename:4,alt:4,type:4,width:2,height:2|1
SELECT idimage,filename,alt,type,width,height FROM _images WHERE idimage=:id

--:infofromalt:R gets image info from an image id
-->alt:4
--<imageid:4,filename:4,alt:4,type:4,width:2,height:2|1
SELECT idimage,filename,alt,type,width,height FROM _images WHERE alt=:alt

--:insert:C inserts a new image
-->filename:4,alt:4,type:4,width:2,height:2
INSERT INTO _images (filename,alt,type,width,height) VALUES (:filename,:alt,:type,:width,:height)

--:files:R gets all cached file names and sizes for an image
-->id:4
--<filenum:2,width:2,height:2
SELECT filenum,width,height FROM _imagefiles WHERE idimage=:id

--:insertfile:C inserts a new record of an actual image file in cache
-->id:4,width:2,height:2
INSERT INTO _imagefiles (idimage,width,height) VALUES (:id,:width,:height)

--:findfile:R looks for a filename in the image table
-->filename:4
--<id:4|1
SELECT idimage FROM _images WHERE filename=:filename

--:update:D updates image data given an id
-->id:4,alt:4,type:4,height:2,width:2
UPDATE _images SET alt=:alt,type=:type,height=:height,width=:width WHERE idimage=:id

--:updatefile:D update a file name
-->id:4,filename:4
UPDATE _images SET filename=:filename where idimage=:id

--:delete:D deletes an image
-->id:4
DELETE FROM _images WHERE idimage=:id

--:idsbydatedesc:R returns all ids sorted by descending date
--<imageid:4
SELECT idimage FROM _images ORDER BY entered DESC

--:idsbydate:R returns all ids sorted by date
--<imageid:4
SELECT idimage FROM _images ORDER BY entered

--:idsbyname:R returns all ids sorted by descending date
--<imageid:4
SELECT idimage FROM _images ORDER BY alt

--:idsbydatedescfiltered:R returns all ids sorted by descending date
-->filter:4
--<imageid:4
SELECT idimage FROM _images WHERE alt LIKE :filter ORDER BY entered DESC

--:idsbydatefiltered:R returns all ids sorted by date
-->filter:4
--<imageid:4
SELECT idimage FROM _images WHERE alt LIKE :filter ORDER BY entered

--:idsbynamefiltered:R returns all ids sorted by descending date
-->filter:4
--<imageid:4
SELECT idimage FROM _images WHERE alt LIKE :filter ORDER BY alt
