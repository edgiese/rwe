--sql queries for the 'email & name' profile storage module

--:idexists:R returns count of ids matching, either 0 or 1
-->id:2
--<count:2|1
SELECT COUNT(userid) FROM emailname_main WHERE userid=:id

--:allids:R return all ids in the profile list
--<id:2
SELECT userid FROM emailname_main

--:idsbyname:R return all ids in the profile list matching a name
-->name:name
--<id:2
SELECT userid FROM emailname_main WHERE title=:name_1 AND firstname=:name_2 AND middlename=:name_3 AND lastname=:name_4 AND generation=:name_5

--:idsbyemail:R return all ids in the profile list matching an email
-->email:email
--<id:2
SELECT userid FROM emailname_main WHERE email=:email_1

--:idsbynameandemail:R return all ids in the profile list matching a name and an email
-->name:name
--<id:2
SELECT userid FROM emailname_main WHERE email=:email_1 AND title=:name_1 AND firstname=:name_2 AND middlename=:name_3 AND lastname=:name_4 AND generation=:name_5

--:create:C inserts a new profile
-->name:name,email:email,profile:4
INSERT INTO emailname_main (title,firstname,middlename,lastname,generation,email,profile) VALUES (:name_1,:name_2,:name_3,:name_4,:name_5,:email_1,:profile)

--:createwithid:C inserts a new profile
-->id:2,profile:4
INSERT INTO emailname_main (userid,title,firstname,middlename,lastname,generation,email,profile) VALUES (:id,:name_1,:name_2,:name_3,:name_4,:name_5,:email_1,:profile)

--:update:D updates a profile
-->id:2,name:name,email:email,profile:4
UPDATE emailname_main SET title=:name_1,firstname=:name_2,middlename=:name_3,lastname=:name_4,generation=:name_5,email=:email_1,profile=:profile WHERE userid=:id

--:read:R reads a profile
-->id:2
--<name:name,email:email,profile:4
SELECT title,firstname,middlename,lastname,generation,email,profile FROM emailname_main WHERE userid=:id

