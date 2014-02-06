------------- search related queries for filegen

--:deleteAllSrchIndex:D clears out all search entry index data
TRUNCATE search_index

--:deleteAllKeywords:D clears out all search keywords
TRUNCATE search_words

--:idFromSearchWord:R finds an id for a search word
-->word:4
--<id:2|1
SELECT id FROM search_words WHERE word=:word

--:searchWordFromId:R finds the word for
-->id:2
--<word:4|1
SELECT word FROM search_words WHERE id=:id

--:addSearchWord:C adds a new search word to the list
-->word:4
INSERT INTO search_words (word) VALUES (:word)

--:clearIndexForId:D removes all index data for a particular text item
-->idtext:4
DELETE FROM search_index WHERE idtext=:idtext

--:addIndexItem:C adds a new entry into the index for a word and text
-->idtext:4,idword:2,count:2
INSERT INTO  search_index (idtext,idword,wcount) VALUES (:idtext,:idword,:count)

--:getAllMatchingIds:R gets list of all text ids containing a word
-->idword:2
--<idtext:4,wcount:2
SELECT idtext,wcount FROM  search_index WHERE idword=:idword ORDER BY wcount DESC

--:getAllMatchingIds1src:R gets list of all text ids or src 'src' containing a word
-->idword:2,src:2
--<idtext:4,wcount:2
SELECT idtext,wcount FROM search_index INNER JOIN _text ON search_index.idtext=_text.textid WHERE idword=:idword AND src=:src ORDER BY wcount DESC

