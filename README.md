# SGML Object File reader
dumps contents of SGML Object File version 2 files ( .sgo files ) a file format used for updating volkswagen group ( Volksvagen, Audi & Skoda ) ECU firmware. 

## Usage 
```bash
#shows the files data
php read_sgo.php file.sgo

#dumps the contents to a .bin file padded with zeros for parts that has no data.
php read_sgo.php file.sgo dump
```
