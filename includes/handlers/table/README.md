# Tabular Data

Provides assertions against tabular data (CSV and TSV) responses.

* The table pointer uses this syntax: `/{row}/{column}`
* `{row}` is either a number starting at `0` for the first row, or `header` to indicate the header row.
* `{column}` is either a number from `0` or the value of a header cell.
* `{column}` is optional
* Supports comma-, tab- and pipe-separated responses.

    ```csv
    "do","re"
    "foo","bar"
    ```

* These are equivalent: `/header/0` and `/header/do`
* These are equivalent: `/0/1` and `/0/bar`
* `/header` or `/0` would select all the columns in the row, thereby you could assert `count`, `contains`, etc.
* [CSV RFC](https://datatracker.ietf.org/doc/html/rfc4180)
