--:info:R gets file info from an file id
-->id:4
--<fileid:4,filename:4,alt:4,type:4|1
SELECT idfile,filename,alt,filetype FROM _files WHERE idfile=:id

--:infofromalt:R gets file info from an file id
-->alt:4
--<fileid:4,filename:4,alt:4,type:4|1
SELECT idfile,filename,alt,filetype FROM _files WHERE alt=:alt

--:insert:C inserts a new file
-->filename:4,alt:4,type:4
INSERT INTO _files (filename,alt,filetype) VALUES (:filename,:alt,:type)

--:findfile:R looks for a filename in the file table
-->filename:4
--<id:4|1
SELECT idfile FROM _files WHERE filename=:filename

--:update:D updates file data given an id
-->id:4,alt:4,type:4
UPDATE _files SET alt=:alt,filetype=:type WHERE idfile=:id

--:updatefile:D update a file name
-->id:4,filename:4
UPDATE _files SET filename=:filename where idfile=:id

--:delete:D deletes an file
-->id:4
DELETE FROM _files WHERE idfile=:id

--:idsbydatedesc:R returns all ids sorted by descending date
--<fileid:4
SELECT idfile FROM _files ORDER BY entered DESC

--:idsbydate:R returns all ids sorted by date
--<fileid:4
SELECT idfile FROM _files ORDER BY entered

--:idsbyname:R returns all ids sorted by descending date
--<fileid:4
SELECT idfile FROM _files ORDER BY alt

--:idsbytype:R returns all ids sorted by type, then name
--<fileid:4
SELECT idfile FROM _files ORDER BY filetype,alt

--:idsbydatedescfiltered:R returns all ids sorted by descending date
-->filter:4
--<fileid:4
SELECT idfile FROM _files WHERE alt LIKE :filter ORDER BY entered DESC

--:idsbydatefiltered:R returns all ids sorted by date
-->filter:4
--<fileid:4
SELECT idfile FROM _files WHERE alt LIKE :filter ORDER BY entered

--:idsbynamefiltered:R returns all ids sorted by descending date
-->filter:4
--<fileid:4
SELECT idfile FROM _files WHERE alt LIKE :filter ORDER BY alt

--:idsbytypefiltered:R returns all ids sorted by type, then name
-->filter:4
--<fileid:4
SELECT idfile FROM _files WHERE alt LIKE :filter ORDER BY filetype,alt
