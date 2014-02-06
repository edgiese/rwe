--:newentry:C creates a new entry for the wizard
-->email:4,salutation:4,varray:4
INSERT INTO wizard_main (email,salutation,varray) VALUES (:email,:salutation,:varray)

--:updateuid:D
-->id:4,uid:4
UPDATE wizard_main SET uid=:uid WHERE id=:id

--:updatevarray:D
-->id:4,varray:4
UPDATE wizard_main SET varray=:varray WHERE id=:id

--:getdata:R gets id & other data from uid
-->uid:4
--<id:4,email:4,salutation:4,varray:4
SELECT id,email,salutation,varray FROM wizard_main WHERE uid=:uid

