from doctest import script_from_examples
from optparse import Values
import constants
import sqlite3
import os

from functions import show_error_popup, resource_path, set_user_defaults

logger=constants.logging.getLogger(__name__)

class DB:
    def __init__(self, sql_script=None):
        """
        Initialize a new @DB instance

        :param db_path: the name of the database file.  It will be created if it doesn't exist.
        :param sql_script: (file) SQL commands to run if @sqlite3_database is not present
        """
        # Open or create db
        logger.info(f'Importing database: {constants.db_path}')

        db_exists   = os.path.isfile(constants.db_path)
        self.con    = sqlite3.connect(constants.db_path)

        if not db_exists:
            self.create_db()


    def create_db(self):
        script      = resource_path('library.sql')
        with open(script, 'r') as file:
            logger.info(f'Loading script {script} into database.')
            self.con.executescript(file.read())

    def get_db_data(self, query, dict=True):
        logger.info('Running query: ' + query)
        cur = self.con.execute(query)

        headings            = [i[0] for i in cur.description]

        rows = cur.fetchall()

        # convert array to dict
        if dict:
            data=[]
            for row_nr, row in enumerate(rows):
                # add a dict
                data.append({})
                for col_nr, value in enumerate(row):
                    data[row_nr][headings[col_nr]]  = value
        #conver array of tuples to array of arrays
        else:
            data=[]
            for row_nr, row in enumerate(rows):
                # add a dict
                data.append([])
                for col_nr, value in enumerate(row):
                    data[row_nr].append(value)
        return data

    def update_db_data(self, query):
        logger.info('Running query: ' + query)
        cur = self.con.execute(query)
        self.con.commit()
        return cur.lastrowid

    def add_db_entry(self, table):
        query = f'INSERT INTO {table} DEFAULT VALUES'
        logger.info(query)
        cur = self.con.execute(query)
        self.con.commit()
        id  = cur.lastrowid

        #set default values
        if table == 'Users':
            set_user_defaults(id)
        else:
            constants.current_item_data={'id':id}
        
        return id

    def update_el_in_db(self, el_key, where=None):
        el_key      = el_key.split('__')[0]
        element     = constants.window[el_key]

        if 'save' in element.metadata and element.metadata['save'] == False:
            return

        table       = element.metadata['table']
        column      = element.metadata['column']
        value       = element.get()

        #guess where statement
        if where == None:
            if table == 'Users':
                where   = 'id='+str(constants.current_user_data['id'])
            elif table == 'Items':
                where   = 'id='+str(constants.current_item_data['id'])
            elif table == 'Authors':
                where   = 'id='+str(constants.current_author_data['id'])
            else:
                logger.info(f'Not clear which row to update to for table {table}')
                show_error_popup('Not clear what to update!')
                return
        
        query           = f'UPDATE {table} SET {column}= "{value}" WHERE {where}'
        self.update_db_data(query)

    def close(self):
        self.con.close()

    def fill_selector(self, el_key):
        element     = constants.window[el_key]
        table       = element.metadata['table']
        orderby     = element.metadata['orderby']
        values  = [i[1] for i in self.get_db_data(f'SELECT * FROM "main"."{table}" ORDER BY {orderby}', False)]

        #only show scrollbar if needed
        element.update(values=values)

        return values
