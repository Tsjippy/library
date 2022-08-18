import constants
import functions

sg              = constants.sg
label_width     = constants.label_width
input_width     = constants.input_width

def build_tab():
    data            = constants.db.get_db_data(f'SELECT * FROM "main"."Items" ORDER BY title')

    items           = [i['title'] for i in data]
    if len(items) > 27:
        no_scrollbar    = False
    else:
        no_scrollbar    = True

    #left column within column 2, profile picture
    col_1 = sg.Col(
        [
            [sg.Image(source = functions.resource_path('./pictures/items/default.png'), size=(100, 125), key='item_picture')],
            [sg.FileBrowse('Change picture', change_submits=True, enable_events=True, key='change_item_picture', metadata={'table':'Items', 'type':'item'})]
        ], 
        vertical_alignment='top'
    )

    #right column within column 2, user details

    #only show if there is data
    if 'item_types' in constants.settings:
        types               = constants.settings['item_types'].split("\n")
        type_vis            = True

        # Show scrollbar if more then 5 options, show all options otherwise
        type_rows   = len(types)
        if type_rows > 4:
            type_rows   = 5
            type_scroll = False
        else:
            type_scroll = True

        item_type_selector  = [
            sg.Text('Type:', size=label_width),
            sg.Listbox(types, key='item_type', size=(30, type_rows), enable_events=True, no_scrollbar=type_scroll, metadata={'table':'Items', 'column':'title', 'clear':'false'})
        ]
    else:
        item_type_selector  = []

    #only show if there is data
    if 'item_locations' in constants.settings:
        locations = constants.settings['item_locations'].split("\n")

        # Show scrollbar if more then 5 options, show all options otherwise
        location_rows   = len(locations)
        if location_rows > 4:
            location_rows   = 5
            location_scroll = False
        else:
            location_scroll = True

        location_type_selector  = [
            sg.Text('Location:', size=label_width),
            sg.Listbox(locations, key='item_location', size=(30, location_rows), enable_events=True, no_scrollbar=location_scroll, metadata={'table':'Items', 'column':'location'})
        ]
    else:
        locations               = []
        location_type_selector  = []

    authors = [i[1] for i in constants.db.get_db_data(f'SELECT * FROM "main"."Authors" ORDER BY last_name', False)]
    col_2 = sg.Col(
        [
            [
                sg.Text('Title:', size=label_width),
                sg.I(key='item_title', size=input_width, enable_events=True, metadata={'table':'Items', 'column':'title'})
            ],
            item_type_selector,
            [
                sg.Text('Author:', size=label_width),
                sg.Combo(
                    values          = authors, 
                    key             = 'item_author',
                    size            = input_width-2,
                    enable_events   = True,
                    bind_return_key = True,
                    metadata        = {'table':'Items', 'column':'author'},
                )
                #sg.I(key='item_author', size=input_width, enable_events=True, metadata={'table':'Items', 'column':'author'})
            ],
            [
                sg.Text('Call number:', size=label_width),
                sg.I(key='item_call_number', size=input_width, enable_events=True, metadata={'table':'Items', 'column':'call_number'})
            ],
            [
                sg.Text('ISBN:', size=label_width),
                sg.I(key='item_isbn', size=input_width, enable_events=True, metadata={'table':'Items', 'column':'isbn'})
            ],
            [
                sg.Text('Barcode:', size=label_width),
                sg.I(key='item_barcode', size=input_width, enable_events=True, metadata={'table':'Items', 'column':'barcode'})
            ],
            [
                sg.Text('Loaned to:', size=label_width),
                sg.Text(key='item_linked_to', size=input_width, enable_events=True, metadata={'table':'Items', 'column':'linked_to'})
            ],
            [
                sg.Text('Loaned since:', size=label_width),
                sg.Text(key='item_loaned_since', size=input_width, enable_events=True, metadata={'table':'Items', 'column':'loaned_since'})
            ],
            [
                sg.Text('Due date:', size=label_width),
                sg.Text(key='item_due_date', size=input_width, enable_events=True, metadata={'table':'Items', 'column':'due_date'})
            ],
            location_type_selector,
        ],
        vertical_alignment  = 'top'
    )

        # right column, user details

    item_details = [
        [sg.Text(
            key                 = 'item_error', 
            visible             = False,
            text_color          = 'red',
            background_color    = 'white',
            border_width        = 3,
            justification       = 'center',
            expand_x            = True
        )],
        [col_1, col_2],
        [sg.Button(
            'Delete this item',
            key             = 'delete_item',
            button_color    = 'red',
            pad             = ((10, 0), (30, 0)),
            enable_events   = True,
            border_width    = 2
        )]
    ]
        
    #left column, ITEM selector
    col_1    = sg.Col(
        [
            [
                sg.Text('Search:', size=6),
                sg.I(key='item_search', size=28, enable_events=True, metadata={'table':'Items'}),
                sg.Text(
                    key                 = 'item_search_count', 
                    visible             = False,
                    text_color          = 'black',
                    border_width        = 3,
                    justification       = 'center',
                    expand_x            = True
                ),
                sg.Listbox(
                    values          = [],
                    select_mode     = sg.LISTBOX_SELECT_MODE_SINGLE,
                    key             = 'search_item_selector',
                    size            = (35,5), 
                    expand_y        = True, 
                    enable_events   = True,
                    no_scrollbar    = False,
                    visible         = False
                )
            ],
            [sg.Button('Add a new item', key='add_item',border_width = 2)],
            [sg.Listbox(
                values          = items,
                select_mode     = sg.LISTBOX_SELECT_MODE_SINGLE,
                key             = 'item_selector',
                size            = (35,27), 
                expand_y        = True, 
                enable_events   = True,
                no_scrollbar    = no_scrollbar,
                metadata        = {'table': 'Items', 'clear':'false', 'orderby':'title', 'save':False, 'data': data}
            )]
        ],
        justification='left'
    )

    col_2    = sg.Col(
        [[sg.Frame(
            title               = '',
            layout              = item_details,
            visible             = False,
            key                 = 'item_details_frame',
            border_width        = 0
        )]],
        vertical_alignment  = 'top',
        key = 'item_col_2'
    )

    # Main layout
    layout = [
        [col_1, col_2]
    ]

    return layout