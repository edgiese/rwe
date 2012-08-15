--crud queries for audio library

--:create:C creates a new entry into the audio library
-->libname:4,fileid:4,info1:4,info2:4,info3:4,recorddate:5,duration:2
INSERT INTO audiolib_main (libname,fileid,info1,info2,info3,recorddate,duration) VALUES (:libname,:fileid,:info1,:info2,:info3,:recorddate,:duration)

--:allRows:R return ids for all entries in library
-->libname:4
--<identry:4
SELECT identry FROM audiolib_main WHERE libname=:libname ORDER BY recorddate DESC

--:getFileInfo:R gets information about one entry
-->identry:4
--<fileid:4,info1:4,info2:4,info3:4,recorddate:5,duration:2
SELECT fileid,info1,info2,info3,recorddate,duration FROM audiolib_main WHERE identry=:identry

--:update:D updates data for an entry
-->identry:4,fileid:4,info1:4,info2:4,info3:4,recorddate:5,duration:2
UPDATE audiolib_main SET fileid=:fileid,info1=:info1,info2=:info2,info3=:info3,recorddate=:recorddate,duration=:duration WHERE identry=:identry

--:delete:D deletes an entry from the library
-->identry:4
DELETE FROM audiolib_main WHERE identry=:identry
