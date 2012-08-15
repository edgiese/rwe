--if (False === ($row=$qqc->getRows('ec/ec_customer::loadfromemail',1,$email)))
--:loadfromemail:R reads values for email
-->email:4
--<password:4,pw2:4,timestamp:4,object:4|1
SELECT pw,pw2,UNIX_TIMESTAMP(pwtime),obj FROM cust_main WHERE email=:email

--$qqc->act('ec/ec_customer::settemppw',$email,md5($temppw));
--:settemppw:D sets temporary password (timestamp auto updated)
-->email:4,pw:4
UPDATE cust_main SET pw2=:pw WHERE email=:email

--$qqc->act('ec/ec_customer::update',$email,$pw,serialize($this));
--:update:D updates existing values
-->email:4,pw:4,object:4
UPDATE cust_main SET pw=:pw,pw2='(none)',obj=:object WHERE email=:email

--$qqc->act('ec/ec_customer::insert',$email,$pw,serialize($this));
--:insert:D inserts a new record
-->email:4,pw:4,object:4
INSERT INTO cust_main (email,pw,pw2,obj) VALUES (:email,:pw,'(none)',:object)

