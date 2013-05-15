-- Reset all email addresses to development addresses and the password to the word password
UPDATE useraccount set email =  'robert.springfield1@lewis-technologies.com' WHERE email = 'robert.springfield@lewis-technologies.com';
UPDATE useraccount set email =  INSERT(email,LOCATE('@', email)+1, 22,'veritracker.com'), password = sha1('password');

-- kerry.lewis@atechra.com
-- admin@atechra.com
-- 744effe5fc13c94640266995871707876b403bb6