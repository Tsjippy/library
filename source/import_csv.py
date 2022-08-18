import csv
from msilib.schema import tables
import os
import sys
import __main__

from functions import show_error_popup, show_popup
import constants

def add_progress_table(heading, values):
    # First delete any previous one
    if 'progress_table_frame' in constants.window.key_dict:
        constants.window['import_progress_frame'].Widget.destroy()

    rows=[]
    # CHange to array of arrays
    for row in values:
        rows.append(list(row.values()))

    # We wrap the table in a frame as tables cannot be removed
    frame = [constants.sg.Frame(
        title           = '',
        border_width    = 0,
        key             = 'progress_table_frame',
        layout          =   [     
            [constants.sg.Table(
                values                  = rows, 
                headings                = heading,
                num_rows                = 10,
                alternating_row_color   = 'darkgrey',
                expand_x                = True,
                justification           = 'center',
                display_row_numbers     = True,
                key                     = 'progress_table'

            )]
        ]
    )]
    constants.window.extend_layout(constants.window['import_progress_frame'], [frame])
           

def import_users(path):
    import_stuff(path, 'user')

def import_items(path):
    import_stuff(path, 'item')

def import_stuff(path, type):
    if type == 'item':
        columns         = constants.itemfields
        table           = 'Items'
    else:
        columns         = constants.userfields
        table           = 'Users'

    # Check if there is aleady data
    result = constants.db.get_db_data(f'SELECT * FROM "main"."{table}"', False)
    if len(result)>0:
        #    Ask f we should remove existing
        answer  = constants.sg.popup_yes_no(
            f'Do you want to remove all existing {type}s first before doing the import?'
        )
        if answer == 'Yes':
            # Clear table
            constants.db.update_db_data(f'DELETE FROM {table}')

    import_fields   = str(columns).replace('[','').replace(']','')

    with open(path, newline='') as csvfile:
        try:
            reader      = csv.DictReader(csvfile, delimiter=';')
            rows        = list(reader)
            total_rows  = len(rows)
            headings    = reader.fieldnames

            #check if valid fields
            for fieldname in headings:
                if not fieldname.lower() in columns:
                    show_error_popup(f'Error importing: invalid field name "{fieldname}"\n\nUse one of the folowing:\n {import_fields}')
                    return

            try:
                constants.window['import_progress_frame'].update(visible=True)
            except:
                # Create new
                __main__.refresh_window(True)
                constants.window['import_progress_frame'].update(visible=True)

            constants.window['import_progress_message'].update('Importing data for: '+str(headings).replace('[','').replace(']',''))
            
            add_progress_table(headings, rows)

            #loop over all the rows
            for row_nr, row in enumerate(rows):
                #check if row has data
                empty   = True
                for data in row:
                    if row[data] != '':
                        empty = False
                        break
                if empty:
                    continue

                # scroll the table
                constants.window["progress_table"].Widget.see(row_nr+1)

                #update the progress bar
                percent     = int((row_nr+1)/total_rows*100)
                constants.window['import_progress_bar'].UpdateBar(percent)
                constants.window['import_progress_percent'].update(f'{percent}%')

                # add db entry
                id  = constants.db.add_db_entry(table)

                # Build the update query
                query           = f'UPDATE {table} SET '
                for i,column in enumerate(row):
                    if i > 0:
                        query   += ', '
                    query   += column.lower()+ '= "'+row[column]+'" '

                query   += f'WHERE id={id}'

                # Run the update query
                constants.db.update_db_data(query)

            #finished remove the table
            constants.window['import_progress_frame'].Widget.destroy()

            # Fill the dropdown
            constants.db.fill_selector(f'{type}_selector')
            
            #show message
            show_popup(f'Succesfully imported {total_rows} {type}s') 
        except Exception as e:
            show_error_popup("Error importing: "+str(e))