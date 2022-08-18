from dataclasses import dataclass
from datetime import date, datetime
from operator import index
from PIL import Image
import os
import time
import sys

import constants

logger=constants.logging.getLogger(__name__)

# map relative path to full path for exe
def resource_path(relative_path):
    """ Get absolute path to resource, works for dev and for PyInstaller """
    try:
        # PyInstaller creates a temp folder and stores path in _MEIPASS
        base_path = sys._MEIPASS
    except Exception:
        base_path = os.path.abspath(".")

    return os.path.join(base_path, relative_path)

def update_selector(el_key, value):
    window  = constants.window
    # get current user list
    values  = window[el_key].GetListValues()

    # current selected index
    index   = window[el_key].get_indexes()[0]
    
    # Update list options
    values[index]   = value

    # Update the selector Listbox
    window[el_key].update(values=values)

    # Select updated option
    window[el_key].update(set_to_index=[index], scroll_to_index=index)

    window.refresh()

def show_picture(el_key, data):
    # Show item picture
    source  = data['picture']
    type    = el_key.replace('_picture','')
    if source == None or not os.path.isfile(source):
        # No picture set, show default
        source  = resource_path('./pictures/items/default.png')

        button_text     = 'Add a picture'
    else:
        button_text     = 'Change picture'
    
    if f'change_{type}_picture' in constants.window.key_dict:
        constants.window[f'change_{type}_picture'].update(button_text)

    #change the image
    constants.window[el_key].update(source=source, size=(constants.im_width, constants.im_height))

def show_popup(text):
    constants.sg.popup_auto_close(
        text, 
        no_titlebar     = True, 
        button_type     = constants.sg.POPUP_BUTTONS_NO_BUTTONS, 
        background_color= 'white', 
        text_color      = constants.sg.theme_background_color(),
        non_blocking    = True
    )

def show_error_popup(text):
    constants.sg.popup_error(
        text, 
        no_titlebar     = True, 
        #button_type     = constants.sg.POPUP_BUTTONS_NO_BUTTONS, 
        background_color= 'white', 
        text_color      = 'red'
    )

# Resize images and convert to png if needed
def image_resize(path, type='user'):
    # open the image for reading
    image = Image.open(path)

    #resize the image proportionally
    image.thumbnail((constants.im_width, constants.im_height))

    # get params
    filename, ext   = os.path.splitext(path)
    filename        = os.path.basename(path)
    pictures_dir    = os.getcwd()+"/pictures/"+type+"s/"

    # Create folder if needed
    if not os.path.exists(pictures_dir):
        os.makedirs(pictures_dir)

    # Images should be PNG
    if not ext == '.PNG':
        filename=filename.replace(ext, '.PNG')
    
    path    = pictures_dir+filename

    # save in new dir
    image.save(path)
    return path

#display date in nice format
def epoch_to_string(epoch):
    return datetime.utcfromtimestamp(epoch).strftime('%d %b `%y')

def update_inputs(type, data):
    window  = constants.window
    db      = constants.db

    window[f'{type}_details_frame'].update(visible=True)

    if type == 'user':
        if len(data) == 0:
            data    = []
        else:
            constants.current_user_data = data
    elif type == 'item':
        if len(data) == 0:
            data    = []
        else:
            constants.current_item_data = data
    elif type == 'author':
        if len(data) == 0:
            data    = ''
        else:
            constants.current_author_data = data

    for key, value in data.items():
        #check if we have an element for this key
        if f'{type}_'+key in window.key_dict:
            try:
                if value == None:
                    value = ''
                window[f'{type}_'+key].update(value)
            except:
                logger.info(f'failed')

    if type != 'author':
        #if there is a picture set, show it
        show_picture(f'{type}_picture', data)

    return data

def select_author(data, window):
    window['author_col_2'].update(visible=True)
    update_inputs('author', data)

def select_user(data, window):
    window['user_col_2'].update(visible=True)

    window  = constants.window

    data = update_inputs('user', data)

    """ 
    #
    # show loan period in days
    # 
    """
    #seconds to days
    try:
        window['user_loan_period'].update(value=int(data['loan_period']/60/60/24))
    except:
        window['user_loan_period'].update(21)    

    """ 
    #
    # update borrowed items table
    # 
    """
    update_borrowed_items_table(data['id'], 'borrowed_items_table')

    window.refresh()

def select_item(data, window):
    window['item_col_2'].update(visible=True)

    update_inputs('item', data)

    window  = constants.window
    db      = constants.db
    
    """ 
    #
    # Display username in stead of user id
    # 
    """
    try:
        user_id             = int(data['linked_to'])
        query               = f'SELECT * FROM "main"."Users" WHERE id="{user_id}"'
        display_name        = db.get_db_data(query)[0]['display_name'] 

        window['item_linked_to'].update(display_name)
    except Exception as e:
        window['item_linked_to'].update('Available')

    """ 
    #
    # format date
    # 
    """
    try:
        loaned_since         = int(data['loaned_since'])
        window['item_loaned_since'].update(epoch_to_string(loaned_since))
    except:
        window['item_loaned_since'].update('NA')

    try:
        due_date         = int(data['due_date'])
        window['item_due_date'].update(epoch_to_string(due_date))
    except:
        window['item_due_date'].update('NA')

    """ 
    #
    # Select item type
    # 
    """
    #select new entry
    if 'item_types' in constants.settings:
        types = constants.settings['item_types'].split("\n")
        type  = data['item_type']
        if type in types:
            index = types.index(type)
        else:
            index = -1
        window[f'item_type'].update(set_to_index=[index], scroll_to_index=index)

    """ 
    #
    # Select item location
    # 
    """
    #select new entry
    if 'item_locations' in constants.settings:
        locations = constants.settings['item_locations'].split("\n")
        location  = data['item_location']
        if location in locations:
            index     = locations.index(location)
        else:
            index = -1
        window[f'item_location'].update(set_to_index=[index], scroll_to_index=index)

def update_borrowed_items_table(user_id, el_key):
    query                       = 'SELECT * FROM "main"."Items" WHERE linked_to ='+str(user_id)
    loaned_items                = constants.db.get_db_data(query, False)

    # Store for later use when clicked on table row
    constants.loaned_items_data = constants.db.get_db_data(query)

    for i, row in enumerate(loaned_items):
        # Show nice formatted dates in table
        loaned_items[i][9]  = epoch_to_string(loaned_items[i][9])
        #also add the due date as a number
        loaned_items[i].append(row[10])
        loaned_items[i][10]  = epoch_to_string(loaned_items[i][10])

    # Update the table
    constants.window[el_key].update(values=loaned_items, num_rows=len(loaned_items))

    # Mark any rows with expired loans, we can only do this after filling the table
    query               = f'SELECT * FROM "main"."Settings" WHERE key ="prewarning_time"'
    data                = constants.db.get_db_data(query)
    if len(data) == 0:
        prewarning_time = 2
    else:
        prewarning_time     = int(data[0]['value'])

    for index, row in enumerate(loaned_items):
        if row[11] < time.time():
            constants.window[el_key].update(row_colors=[(index,'red')])
        elif row[11] < int(time.time())+prewarning_time:
            constants.window[el_key].update(row_colors=[(index,'orange')])

    return loaned_items

def set_user_defaults(id):
    query       = f'SELECT * FROM "main"."Settings" where key="max_items"'
    data        = constants.db.get_db_data(query)
    if len(data) == 0:
        max_items = 5
    else:
        max_items   = data[0]['value']

    query       = f'SELECT * FROM "main"."Settings" where key="loan_period"'
    data        = constants.db.get_db_data(query)
    if len(data) == 0:
        loan_days = 21
    else:
        loan_days   = int(data[0]['value'])
    
    loan_second = loan_days*24*60*60
    query       = f'UPDATE Users SET max_items = "{max_items}", loan_period = {loan_second} WHERE id={id}'
    constants.db.update_db_data(query)
    constants.current_user_data={'id':id, 'max_items':max_items, 'loan_period':loan_second}

    constants.window['user_max_items'].update(max_items)
    constants.window['user_loan_period'].update(loan_days)