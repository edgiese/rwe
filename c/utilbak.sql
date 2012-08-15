--:posdelete:D deletes a pos entry
-->key:4
DELETE FROM _pos WHERE posid=:key

--:posdeletedata:D deletes pos data blocks for a key
-->key:4
DELETE FROM _posdata WHERE posindex=:key

--:updatepos:D updates a pos table entry
-->key:4,type:4,rev:2,nBlocks:2
UPDATE _pos SET type=:type,rev=:rev,`count`=:nBlocks,lasthit=CURRENT_TIMESTAMP WHERE posid=:key

--:posdata:R gets posdata blocks (nBlocks of them) for a key
-->key:4,nBlocks:2
--<data:4
SELECT `data` FROM _posdata WHERE posindex=:key AND `sequence` < :nBlocks ORDER BY `sequence`

--:pos:R gets rev and count for a pos key
-->key:4
--<rev:2,count:2|1
SELECT rev,`count` FROM _pos WHERE posid=:key

--:updatecount:D updates the block count of a pos entry
-->key:4,nBlocks:2
UPDATE _pos SET `count`=:nBlocks WHERE posid=:key

--:pos:C creates a new pos entry
-->type:4,session:4,rev:2,nBlocks:2,timeout:2
INSERT INTO _pos (type,session,rev,`count`,timeout) VALUES (:type,:session,:rev,:nBlocks,:timeout)

--:flushpos:D flushes old data from the pos table
DELETE FROM _pos,_posdata USING _pos INNER JOIN _posdata on posid=posindex WHERE timeout > 0 AND TIMESTAMPDIFF(SECOND,lasthit,CURRENT_TIMESTAMP) > timeout 

--:insertposblock:D inserts a new data block for pos
-->key:4,index:2,data:4
INSERT INTO _posdata (posindex,`sequence`,`data`) VALUES (:key,:index,:data)

--:updateposblock:D updates a pos block's data
-->key:4,blockix:2,data:4
UPDATE _posdata SET `data`=:data WHERE posindex=:key AND `sequence`=:blockix

--:countposblocks:R counts pos blocks for a key
-->key:4
--<count:2|1
SELECT COUNT(*) FROM _posdata WHERE posindex=:key

--:posbykey:R gets pos keys for a saved object from a session key and class type
-->key:4,type:4
--<objkey:4
SELECT posid FROM _pos WHERE session=:key AND type=:type

--:sessionkeyexists:R returns the count of session entries with a key - 1 or 0
-->key:4
--<count:2|1
SELECT COUNT(*) FROM _pos WHERE type='state' AND session=:key

--:postimestamp:R gets timestamp of a pos object from its key
-->key:4
--<timestamp:2|1
SELECT UNIX_TIMESTAMP(lasthit) FROM _pos WHERE posid=:key
