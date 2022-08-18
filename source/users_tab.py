from cgitb import text
from pydoc import visiblename
import constants

#layout for loaned item overview
def loaned_items_frame(key, visible=True):
    # Replace any _ with a space and capitalize
    headings = list(map(lambda text: text.replace('_', ' ').capitalize(), constants.itemfields))
    headings.append('epoch')
    layout    = [
        [sg.Table(
            values                  = [], 
            headings                = headings,
            num_rows                = 5,
            alternating_row_color   = 'darkgrey',
            hide_vertical_scroll    = True,
            expand_x                = True,
            key                     = key+'_table',
            justification           = 'center',
            auto_size_columns       = False,
            visible_column_map      = [0,1,0,1,0,0,1,0,0,1,1,0],
            col_widths              = [20,10,10,10],
            enable_click_events     = True
        )],
        [
            sg.Text('Legend: '),
            sg.Text('   ', background_color='red'),
            sg.Text('Expired item'),
            sg.Text('   ', background_color='orange'),
            sg.Text('Item about to expire ')
        ],
    ]

    frame = [sg.Frame(
        'Loaned items: ',
        layout              = layout,
        expand_x            = True,
        vertical_alignment  = 'bottom',
        visible             = visible,
        key                 = key+'_frame'
    )]

    return frame

def user_details():
    #left column within column 2, profile picture
    col_1 = sg.Col(
        [
            [sg.Image(source='', size=(120, 100), key='user_picture')],
            [sg.FileBrowse('Add a picture', change_submits=True, enable_events=True, key='change_user_picture', metadata={'table':'Users','type':'user'})]
        ], 
        vertical_alignment='top'
    )

    #right column within column 2, user details
    col_2 = sg.Col(
        [
            [
                sg.I(key='user_display_name', size=input_width, enable_events=True, visible=False, metadata={'table':'Users', 'column':'display_name'})
            ],
            [
                sg.Text('First name:', size=label_width),
                sg.I(key='user_first_name', size=input_width, enable_events=True, metadata={'table':'Users', 'column':'first_name'})
            ],
            [
                sg.Text('Last name:', size=label_width),
                sg.I(key='user_last_name', size=input_width, enable_events=True, metadata={'table':'Users', 'column':'last_name'})
            ],
            [
                sg.Text('Birthday:', size=label_width),
                sg.I(key='user_birthday', size=15, enable_events=True, metadata={'table':'Users', 'column':'birthday'}),
                sg.CalendarButton(button_text="Choose Date",target='user_birthday', size=10, enable_events=True, format = "%d-%m-%Y")
            ],
            [
                sg.Text('Barcode:', size=label_width),
                sg.I(key='user_barcode', size=input_width, enable_events=True, metadata={'table':'Users', 'column':'barcode'})
            ],
            [
                sg.Text('Max items:', size=label_width),
                sg.I(key='user_max_items', size=input_width, enable_events=True, metadata={'table':'Users', 'column':'max_items'})
            ],
            [
                sg.Text('Loan period:', size=label_width),
                sg.I(key='user_loan_period', size=input_width, enable_events=True, metadata={'table':'Users', 'column':'loan_period'})
            ],
        ],
        vertical_alignment  = 'top'
    )
    
    layout = [
        [sg.Text(
                key                 = 'user_error', 
                visible             = False,
                text_color          = 'red',
                background_color    = 'white',
                border_width        = 3,
                justification       = 'center',
                expand_x            = True
        )],
        [sg.Frame(
            title    = '',
            layout  = [
                [col_1, col_2], 
                loaned_items_frame('borrowed_items'),
                [sg.Button(
                    button_text     = 'Delete this user',
                    key             = 'delete_user',
                    button_color    = 'red',
                    pad             = ((10, 0), (30, 0)),
                    enable_events   = True,
                    border_width    = 2
                )]
            ],
            border_width        = 0,
            visible             = False,
            key                 = 'user_details_frame'
        )]

    ]

    return layout

sg              = constants.sg
label_width     = constants.label_width
input_width     = constants.input_width

def build_tab():
    
    # First delete any previous one
    if constants.window != '' and 'users_main_frame' in constants.window.key_dict:
        constants.window['users_main_frame'].Widget.master.pack_forget()

    data            = constants.db.get_db_data(f'SELECT * FROM "main"."Users" ORDER BY last_name')
    users           = [i['display_name'] for i in data]
    if len(users) > 27:
        no_scrollbar    = False
    else:
        no_scrollbar    = True

    #left column, user selector
    col_1    = sg.Col(
        [
            [
                sg.Text('Search:', size=6),
                sg.I(key='user_search', size=28, enable_events=True, metadata={'table':'Users'}),
                sg.Text(
                    key                 = 'user_search_count', 
                    visible             = False,
                    text_color          = 'black',
                    border_width        = 3,
                    justification       = 'center',
                    expand_x            = True
                ),
                sg.Listbox(
                    values          = [],
                    select_mode     = sg.LISTBOX_SELECT_MODE_SINGLE,
                    key             = 'search_user_selector',
                    size            = (35,5), 
                    expand_y        = True, 
                    enable_events   = True,
                    no_scrollbar    = False,
                    visible         = False
                )
            ],
            [sg.Button('Add a new user', key='add_user', border_width= 2)],
            [sg.Listbox(
                values          = users,
                select_mode     = sg.LISTBOX_SELECT_MODE_SINGLE,
                key             = 'user_selector',
                size            = (35,27), 
                expand_y        = True, 
                enable_events   = True,
                no_scrollbar    = no_scrollbar,
                metadata        = {'table': 'Users', 'clear':'false', 'orderby':'last_name', 'save':False, 'data':data}
            )]
        ],
        justification='left',
    )

    col_2    = sg.Col(
        user_details(),
        vertical_alignment  = 'top',
        key = 'user_col_2'
    )

    # Main layout
    layout = [[col_1, col_2]]

    return layout

    frame = [[sg.Frame(
        title       = '',
        layout      = layout,
        key         = 'users_main_frame',
        border_width= 0
    )]]

    return frame