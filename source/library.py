import time
import constants
import sql_functions

# load or create our database as early as possible
constants.db        = sql_functions.DB('library.sql')
query               = f'SELECT * FROM "main"."Settings"'
data                = constants.db.get_db_data(query)
constants.settings  = {}

# Fill constants with global settings
for row in data:
    key                     = row['key']
    value                   = row['value']
    constants.settings[key] = value

constants.set_theme()

# own files
import users_tab
import items_tab
import check_out_tab
import settings_tab
import authors_tab
from functions import *
from actions import *
from import_csv import *

sg              = constants.sg
new_author_name = ''

def make_window():
    # Tab definitions
    layout = [
        [sg.TabGroup(
            [
                [
                    sg.Tab(
                        'Check out', 
                        check_out_tab.build_tab(), 
                        border_width            = 10, 
                        tooltip                 = 'Check out or check in items', 
                        element_justification   = 'center',
                    ),
                    sg.Tab(
                        'Users', 
                        users_tab.build_tab(), 
                        border_width            = 10, 
                        tooltip                 = 'Create and manage library users', 
                        element_justification   = 'center',
                        key                     = 'users_tab'
                    ),
                    sg.Tab(
                        'Items', 
                        items_tab.build_tab(), 
                        border_width            = 10, 
                        tooltip                 = 'Create and manage library items', 
                        element_justification   = 'center',
                        key                     = 'items_tab'
                    ),
                    sg.Tab(
                        'Settings', 
                        settings_tab.build_tab(),  
                        border_width            = 10,  
                        tooltip                 = 'Software settings', 
                        element_justification   = 'center'
                    ),
                    sg.Tab(
                        'Authors', 
                        authors_tab.build_tab(),  
                        border_width            = 10,  
                        tooltip                 = 'Authors', 
                        element_justification   = 'center'
                    )
                ]
            ],
            border_width    = 1,
            enable_events   = True
        )
        ]
    ]

    # Create the Window
    query   = f'SELECT * FROM "main"."Settings" where key="program_title"'
    result  = constants.db.get_db_data(query)
    if len(result) == 0 or result[0]['value'] == '':
        title   = 'Library'
    else:
        title   = result[0]['value']
    
    # Hide the new window if refreshing
    if constants.window == '':
        hide=False
    else:
        hide=True
    
    # Create window
    constants.window = sg.Window(title, layout, finalize=True, no_titlebar=False, grab_anywhere=True)
    if hide:
        constants.window.hide()
    
    constants.window.set_icon(pngbase64=constants.icon)

    for type in ['item', 'user']:
        constants.window[f'{type}_selector'].bind("<Up>", "__up__")
        constants.window[f'{type}_selector'].bind("<Down>", "__down__")
    constants.window[f'item_author'].bind("<Key>", "__key__")

def refresh_window(force = False):
    if constants.refresh or force:
        constants.refresh   = False
        # Close old window
        old=constants.window

        # Create new
        make_window()

        #select the tab
        constants.window[constants.current_tab].select()

        #reset data
        constants.current_user_data = ''
        constants.current_item_data = ''

        # show the new window
        constants.window.UnHide()

        #close the old window
        old.close()

def add_author(display_name):
    global new_author_name
    new_author_name = ''
    names           = display_name.split(' ')
    first_name      = names[0]
    if len(names) == 1:
        last_name   = ''
    else:
        last_name   = ' '.join(names[1:])

    query       = f'SELECT * FROM "main"."Authors" where display_name="{display_name}"'
    result      = constants.db.get_db_data(query)

    if len(result) == 0:
        query   = f'INSERT INTO Authors (display_name, first_name, last_name) VALUES ("{display_name}","{first_name}","{last_name}")'
        result  = constants.db.update_db_data(query)

        # Update selector
        constants.db.fill_selector(f'author_selector')

def start():
    global new_author_name

    make_window()

    # Event Loop to process "events" and get the "values" of the inputs
    while True:
        event, values = constants.window.read()

        window  = constants.window

        # Add a new author when the new_author_name variable is not empty and 
        # the current event is not mapped to the author field
        if not new_author_name == '' and (not isinstance(event, str) or not event.startswith('item_author')):
            add_author(new_author_name)

        if isinstance(event, int):
            # We are dealing with a tab click
            constants.current_tab = values[event]
            if constants.current_tab == 'users_tab' or constants.current_tab == 'items_tab':
                refresh_window()
                # we have to read it to prevent a loop
                constants.window.read()
            else:
                continue
        elif event == sg.WIN_CLOSED or event == 'Exit':
            constants.db.close()
            break
        elif event == 'user_selector':
            logger.info(f'Selecting user')

            # current selected index
            index   = window['user_selector'].get_indexes()[0]
            # Get item barcode from metadata and select it
            select_user(window['user_selector'].metadata['data'][index], window)
        elif event == 'item_selector':
            logger.info(f'Selecting item')

            # current selected index
            index   = window['item_selector'].get_indexes()[0]
            # Get item barcode from metadata and select it
            select_item(window['item_selector'].metadata['data'][index], window)
        elif event == 'author_selector':
            logger.info(f'Selecting author')

            index   = window['author_selector'].get_indexes()[0]
            # Get item barcode from metadata and select it
            select_author(window['author_selector'].metadata['data'][index])
        elif event == 'item_type':
            if len(values[event]) == 0:
                continue
            value   = values[event][0]
            id      = constants.current_item_data['id']
            query   = f'UPDATE Items SET item_type= "{value}" WHERE id={id}'
            constants.db.update_db_data(query)
        elif event == 'user_search':
            logger.info('Processing user search')
            user_search(values[event], window)
        elif event == 'item_search':
            logger.info('Processing item search')
            item_search(values[event], window)
        elif event == 'author_search':
            logger.info('Processing item search')
            author_search(values[event], window)
        elif event == 'user_first_name' or event == 'user_last_name':
            logger.info('Updating displayname')
            update_user_name()
        elif event == 'user_birthday':
            constants.db.update_el_in_db(event)
        elif event == 'user_loan_period' or event == 'user_barcode' or event == 'user_max_items':
            if event == 'user_barcode':
                # make sure the barcode is unique
                query   = f'SELECT barcode FROM "main"."Users" WHERE barcode = "{values[event]}" union SELECT barcode FROM "main"."Items" WHERE barcode = "{values[event]}"'
                data    = constants.db.get_db_data(query)
                if(data != []):
                    window['user_error'].update('Entry with barcode '+values[event]+' already exists!', visible=True)
                    continue
                else:
                    window['user_error'].update(visible=False)

            logger.info('Validating number for '+event)
            #if there is an input
            if not values[event] == '':
                #check if int
                if isinstance(values[event], int) or isinstance(values[event], str) and values[event].isnumeric():
                    if event == 'user_loan_period':
                        days    = int(window['user_loan_period'].Get())
                        seconds = days*24*60*60

                        id=constants.current_user_data['id']

                        #save
                        query   = f'UPDATE Users SET loan_period= "{seconds}" WHERE id={id}'
                        constants.db.update_db_data('user_loan_period')
                    else:
                        #save
                        constants.db.update_el_in_db(event)
                else:
                    #clear the input
                    window[event].update('')

                    #show the warning
                    show_error_popup('You should only enter a number here')
        elif event == 'change_user_picture' or event == 'change_item_picture':
            table   = window[event].metadata['table']
            type    = window[event].metadata['type']
            logger.info(f'Processing {type} picture')

            if(type == 'item'):
                id      = constants.current_item_data['id']
            else:
                id      = constants.current_user_data['id']

            #if there is a picture selected
            if values[event] != '':
                #copy picture and change dimensions and filetype if needed
                new_path    = image_resize(values[event], type)

                #store new path in db
                query       = f'UPDATE {table} SET picture = "{new_path}" WHERE id={id}'
                constants.db.update_db_data(query)                

                #update the picture
                window[f'{type}_picture'].update(source=new_path, size=(constants.im_width, constants.im_height))

                # Update the button text
                window[f'change_{type}_picture'].update('Change picture')
        elif event == 'check_out':
            logger.info('Processing check out')

            user_id     = constants.current_user_data['id']
            name        = constants.current_user_data['display_name']         
            barcode     = window['checkout_item_barcode'].Get()
            title       = window['checkout_title'].Get()
            
            now         = int(time.time())

            query       = 'SELECT loan_period FROM "main"."Users" WHERE id ='+str(user_id)
            data        = constants.db.get_db_data(query)
            due_date    = now + data[0]['loan_period']

            query       = f'UPDATE Items SET linked_to = {user_id}, loaned_since = {now}, due_date = {due_date} WHERE barcode="{barcode}"'
            constants.db.update_db_data(query)

            #refresh the screen
            checkout_user_search(constants.current_user_data['display_name'])

            checkout_item_search('')

            show_popup(f'Succesfully loaned {title} to {name} till '+epoch_to_string(due_date))
        elif event == 'check_in':
            logger.info('Processing check_in')

            barcode     = window['checkout_item_barcode'].Get()
            title       = window['checkout_title'].Get()

            query       = f'UPDATE Items SET linked_to="", loaned_since="", due_date="" WHERE barcode="{barcode}"'
            constants.db.update_db_data(query)

            #refresh the screen
            checkout_user_search(constants.current_user_data['display_name'])

            checkout_item_search('')

            show_popup(f'Succesfully returned {title} to the library')     
        elif event == 'extend_loan':
            logger.info('Processing extending loan')

            user_id     = constants.current_user_data['id']
            barcode     = window['checkout_item_barcode'].Get()

            query       = 'SELECT loan_period FROM "main"."Users" WHERE id ='+str(user_id)
            data        = constants.db.get_db_data(query)
            extension   = data[0]['loan_period']

            #add the extension period to the existing due date
            query       = f'UPDATE Items SET due_date=due_date + {extension} WHERE barcode="{barcode}"'
            constants.db.update_db_data(query)

            #refresh the screen
            checkout_user_search(constants.current_user_data['display_name'])

            checkout_item_search('')

            extension_days  = int(extension/60/60/24)
            show_popup(f'Succesfully extended the loan with {extension_days} days')
        elif isinstance(event, tuple) and event[0] == 'borroweditems_table':
            #get the row number from the values
            row_nr  = event[2][0]

            #show the clicked item
            checkout_show_item(constants.loaned_items_data[row_nr])
        elif event == 'checkout_user_search':
            checkout_user_search(values[event])
        elif event == 'checkout_item_search':
            checkout_item_search(values[event])
        elif event.startswith('delete_'):
            delete_entry(event.replace('delete_',''))
        elif event == 'import_users':
            import_users(values[event])
        elif event == 'import_items':
            import_items(values[event])
        elif event == 'search_item_selector':
            # current selected index
            index   = window['search_item_selector'].get_indexes()[0]

            #hide the search results
            window['item_search'].update('')
            window['item_search_count'].update('', visible=False)
            window['search_item_selector'].update('', visible=False)
            # Get item barcode from metadata and select it
            select_item(window['search_item_selector'].metadata[index], window)
        elif event == 'search_user_selector':
            # current selected index
            index   = window['search_user_selector'].get_indexes()[0]

            #hide the search results
            window['user_search'].update('')
            window['user_search_count'].update('', visible=False)
            window['search_user_selector'].update('', visible=False)
            # Get item barcode from metadata and select it
            select_user(window['search_user_selector'].metadata[index], window)
        elif event == 'search_author_selector':
            # current selected index
            index   = window['search_author_selector'].get_indexes()[0]

            #hide the search results
            window['author_search'].update('')
            window['author_search_count'].update('', visible=False)
            window['search_author_selector'].update('', visible=False)
            # Get item barcode from metadata and select it
            select_author(window['search_author_selector'].metadata[index])
        elif '__up__' in event:
            el_key  = event.replace('__up__', '')
            element = window[el_key]
            cur_index   = element.Widget.curselection()
            new_index   = (cur_index[0] - 1) % element.Widget.size()
            element.update(set_to_index=[new_index])

            #update screen
            if 'user' in el_key:
                index   = window['user_selector'].get_indexes()[0]
                select_user(window['user_selector'].metadata['data'][index], window)
            elif 'item' in el_key:
                index   = window['item_selector'].get_indexes()[0]
                select_item(window['item_selector'].metadata['data'][index], window)
            elif 'author' in el_key:
                index   = window['author_selector'].get_indexes()[0]
                select_author(window['author_selector'].metadata['data'][index], window)

        elif '__down__' in event:
            el_key  = event.replace('__down__', '')
            element = window[el_key]
            cur_index   = element.Widget.curselection()
            new_index   = (cur_index[0] + 1) % element.Widget.size()
            element.update(set_to_index=[new_index])

            #update screen
            if 'user' in el_key:
                index   = window['user_selector'].get_indexes()[0]
                select_user(window['user_selector'].metadata['data'][index], window)
            elif 'item' in el_key:
                index   = window['item_selector'].get_indexes()[0]
                select_item(window['item_selector'].metadata['data'][index], window)
            elif 'author' in el_key:
                index   = window['author_selector'].get_indexes()[0]
                select_author(window['author_selector'].metadata['data'][index], window)
        elif '__key__' in event:
            el_key  = event.replace('__key__', '')
            element = window[el_key]

            if el_key == 'item_author':
                # store name to be used when finished typing
                new_author_name = element.get()
        elif event.startswith('add_'):
            add_entry(event.replace('add_',''))
        elif event.startswith('item_'):
            if event == 'item_barcode':
                # make sure the barcode is unique
                query   = f'SELECT barcode FROM "main"."Users" WHERE barcode = "{values[event]}" union SELECT barcode FROM "main"."Items" WHERE barcode = "{values[event]}"'
                data    = constants.db.get_db_data(query)
                if(data != []):
                    window['item_error'].update('Entry with barcode '+values[event]+' already exists!', visible=True)
                    continue
            else:
                window['item_error'].update(visible=False)

            constants.db.update_el_in_db(event)

            if event == 'item_title':
                 # get current item list
                values  = window['item_selector'].GetListValues()

                # current selected index
                index   = window['item_selector'].get_indexes()[0]
                
                # Update list options
                values[index]   = window[event].get()

                # Update the selector Listbox
                window['item_selector'].update(values=values)

                # Select updated option
                window['item_selector'].update(set_to_index=[index], scroll_to_index=index)
            elif event == 'item_author':
                add_author(values[event])
        elif event.startswith('author_'):
            constants.db.update_el_in_db(event)

            first_name  = window['author_first_name'].get()
            last_name   = window['author_last_name'].get()
            window['author_display_name'].update(first_name+' '+last_name)
            constants.db.update_el_in_db('author_display_name')

            update_selector('author_selector', first_name+' '+last_name)
        elif event.startswith('settings_'):
            key     = event.replace('settings_','')
            value   = values[event]

            query       = f'SELECT * FROM "main"."Settings" where key="{key}"'
            result      = constants.db.get_db_data(query)

            if len(result) == 0:
                query   = f'INSERT INTO Settings (key, value) VALUES ("{key}","{value}")'
                result  = constants.db.update_db_data(query)
            else:
                query   = f'UPDATE Settings SET value= "{value}" WHERE key="{key}"'
                result  = constants.db.update_db_data(query)
            
            # Save value in the cached value as well
            constants.settings[key]=value

            if 'window_refresh' in constants.window[event].metadata:
                if constants.window[event].metadata['window_refresh'] == 'force':
                    show_popup('Applying new theme, please wait')
                    constants.set_theme()
                    refresh_window(True)
                else:
                    constants.refresh   = True
        else:
            logger.info(f'This event ({event}) is not yet handled.')

#only run when rn directly, not when imported
if __name__ == "__main__":
    start()