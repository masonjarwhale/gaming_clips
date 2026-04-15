BIG THANKS TO PROFESSOR STEVE FOR CANVAS VIDEO FRAME CAPTURE! CHECK HIM OUT BELOW:\
https://gist.github.com/prof3ssorSt3v3/efcf21c32b1d15e20fa48f57139776a2

Please add a folder named 'modals' beside the rest of these files.\
The table in the database is set up as follows: 
```sql
CREATE TABLE files (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(256),
    thumbnail MEDIUMTEXT, --base64 format
    user VARCHAR(256),
    tag VARCHAR(256),
    modal VARCHAR(256)
);
