--sql queries for the 'individual' authorization module

--:pairexists:R returns count of id and name matching, either 0 or 1
-->id:2,name:4
--<count:2|1
SELECT COUNT(userid) FROM individual_main WHERE userid=:id AND name=:name

--:create:C inserts a new profile
-->id:2,name:4,value:2
INSERT INTO individual_main (userid,name,authvalue) VALUES (:id,:name,:value)

--:update:D updates a profile
-->id:2,name:4,value:2
UPDATE individual_main SET authvalue=:value WHERE userid=:id AND name=:name

--:read:R reads a profile from a name
-->id:2
--<name:4,value:2
SELECT name,authvalue FROM individual_main WHERE userid=:id

