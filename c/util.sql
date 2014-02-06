--:updatestate:D updates a state table entry
-->key:4,statestring:4,lasthit:4
UPDATE _state SET statestring=:statestring,lasthit=:lasthit WHERE statekey=:key

--:stateclean:D flushes old data from the state table
DELETE FROM _state WHERE timeout > 0 AND TIMESTAMPDIFF(SECOND,lasthit,CURRENT_TIMESTAMP) > timeout 

--:state:C creates a new state entry
-->key:4,timeout:2,statestring:4
INSERT INTO _state (statekey,timeout,statestring) VALUES (:key,:timeout,:statestring)

--:state:R gets state string from key
-->key:4
--<statestring:4
SELECT statestring FROM _state WHERE statekey=:key

--:sessionkeyexists:R returns count of a session key
-->key:4
--<count:2
SELECT COUNT(*) FROM _state WHERE statekey=:key

--:newlog:C creates new log entry
-->reqaddr:4,reqlang:4,referredfrom:4,ua:4
INSERT INTO _logheader (reqaddr,reqlang,referredfrom,ua) VALUES (:reqaddr,:reqlang,:referredfrom,:ua)

--:logpage:C new page entry into log
-->logid:4,page:4,extra:4
INSERT INTO _logpage (logid,page,extra) VALUES (:logid,:page,:extra)

--:logevent:C new event entry into log
-->logid:4,evtype:4,evdata:4
INSERT INTO _logevent (logid,evtype,evdata) VALUES (:logid,:evtype,:evdata)

--:logexception:C new exception entry into log
-->logid:4,emessage:4,efile:4,eline:2,etrace:4
INSERT INTO _logexception (logid,emessage,efile,eline,etrace) VALUES (:logid,:emessage,:efile,:eline,:etrace)
