/* this is required to make foreign keys work */
PRAGMA foreign_keys=ON;

/* should be some kind of enum */
CREATE TABLE OidType (
    oTypeID	integer primary key,
    oKind	text);
INSERT INTO OidType (oKind) VALUES ('user');
INSERT INTO OidType (oKind) VALUES ('address');
INSERT INTO OidType (oKind) VALUES ('dealspace');
INSERT INTO OidType (oKind) VALUES ('mimedoc');
INSERT INTO OidType (oKind) VALUES ('attachment');
INSERT INTO OidType (oKind) VALUES ('participant');

CREATE TABLE Oid (
    oid		integer primary key,
    oStr	text,		/* encoded */
    owner	integer,	/* oid:user */
    cTime	text,		/* creation */
    mTime	text,		/* modify */
    nonce	int,		/* insert check */
    oType	int,		/* which table to find it in */
    FOREIGN KEY(oType) REFERENCES OidType(oTypeID)
  );

/* not an OID at all */
CREATE TABLE Attribute (
    referant	integer NOT NULL,		/* attached to */
    aKey	text COLLATE NOCASE,		/* attribute name */
    aValue	text COLLATE NOCASE,		/* attribute value */
    FOREIGN KEY(referant) REFERENCES Oid(oid)
  );

CREATE TABLE User (				/* OidType=2 */
    oid		integer primary key,
    realname	text COLLATE NOCASE,		/* contact */
    email	text COLLATE NOCASE,		/* contact */
    password	text,				/* contact */
    question	text,				/* secret question */
    answer	text COLLATE NOCASE,		/* seekrit answer */
    isadmin	int DEFAULT 0,		/* has seekrit powerz */
    validated	int DEFAULT 0,		/* responded to the email */
    locked	int DEFAULT 0,		/* we can lock them out */
    badpass	int DEFAULT 0,		/* count */
    FOREIGN KEY(oid) REFERENCES Oid(oid) ON DELETE CASCADE
  );

/* we own ourselves */
INSERT INTO Oid (oid,oStr,owner,cTime,mTime,nonce,oType)
  VALUES (1,'deadbeef7',1,'2014-02-14 00:00:00','2014-02-14 00:00:00',0,1);
INSERT INTO User (oid,realname,email,password,question,answer,
  isadmin,validated,locked,badpass)
VALUES (1,'Archbishop Blankenton','archie@sameplace.com',
  '$2y$10$5i0XS0FfYeKFnc.q7r7NX.KYpqCYHXG2MiJU5CRBAL2rHNbvCJMfC',
  'Favorite city?','Paris',
  1,1,0,0);

CREATE TABLE Address (
    oid		integer primary key,
    email	text COLLATE NOCASE,		/* contact */
    validated	int DEFAULT 0,			/* boolean */
    FOREIGN KEY(oid) REFERENCES Oid(oid) ON DELETE CASCADE
  );

CREATE TABLE DealSpace (
    oid		integer primary key,
    editable	int DEFAULT 1,			/* boolean */
    hidden	int DEFAULT 0,			/* boolean */
    name	text COLLATE NOCASE,		/* user-specified */
    FOREIGN KEY(oid) REFERENCES Oid(oid) ON DELETE CASCADE
  );

CREATE TABLE MimeDoc (
    oid		integer primary key,
    deal	integer,			/* part of a deal */
    FromAddr	text COLLATE NOCASE,		/* just the address */
    MessageId	text COLLATE NOCASE,		/* ibid */
    hidden	int DEFAULT 0,			/* boolean */
    private	int DEFAULT 1,			/* boolean */
    FOREIGN KEY(deal) REFERENCES DealSpace(oid),
    FOREIGN KEY(oid) REFERENCES Oid(oid) ON DELETE CASCADE
  );

CREATE TABLE Attachment (
    oid		integer primary key,
    mDoc	integer,	/* part of a MimeDoc */
    mType	text,		/* MIME type */
    name	text,		/* given filename */
    path	text,		/* filesystem */
    FOREIGN KEY(mDoc) REFERENCES MimeDoc(oid),
    FOREIGN KEY(oid) REFERENCES Oid(oid) ON DELETE CASCADE
  );

CREATE TABLE Participant (
    oid		integer primary key,
    deal	integer,			/* part of a deal */
    Role	int DEFAULT 0,			/* enum */
    Addr	text COLLATE NOCASE,		/* just the address */
    Name	text COLLATE NOCASE,		/* optional name */
    FOREIGN KEY(deal) REFERENCES DealSpace(oid),
    FOREIGN KEY(oid) REFERENCES Oid(oid) ON DELETE CASCADE
  );
