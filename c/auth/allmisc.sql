--sql queries for the 'all miscellaneous' profile storage module

--:idexists:R returns count of ids matching, either 0 or 1
-->id:2
--<count:2|1
SELECT COUNT(userid) FROM allmisc_main WHERE userid=:id

--:create:C inserts a new profile
-->profile:4
INSERT INTO allmisc_main (profile) VALUES (:profile)

--:createwithid:C inserts a new profile
-->id:2,profile:4
INSERT INTO allmisc_main (userid,profile) VALUES (:id,:profile)

--:update:D updates a profile
-->id:2,profile:4
UPDATE allmisc_main SET profile=:profile WHERE userid=:userid

--:read:R reads a profile
-->id:2
--<profile:4
SELECT profile FROM allmisc_main WHERE userid=:id

