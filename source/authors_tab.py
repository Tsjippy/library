import constants
import functions

sg              = constants.sg
label_width     = constants.label_width
input_width     = constants.input_width

def build_tab():
    data    = constants.db.get_db_data(f'SELECT * FROM "main"."Authors" ORDER BY last_name')
    authors = [i['display_name'] for i in data]
    if len(authors) > 27:
        no_scrollbar    = False
    else:
        no_scrollbar    = True

    col = sg.Col(
        [
            [
                sg.I(key='author_display_name', size=input_width, enable_events=True, visible=False, metadata={'table':'Authors', 'column':'display_name'})
            ],
            [
                sg.Text('First name:', size=label_width),
                sg.I(key='author_first_name', size=input_width, enable_events=True, metadata={'table':'Authors', 'column':'first_name'})
            ],
            [
                sg.Text('Last name:', size=label_width),
                sg.I(key='author_last_name', size=input_width, enable_events=True, metadata={'table':'Authors', 'column':'last_name'})
            ],
        ],
        vertical_alignment  = 'top'
    )

        # right column, user details

    details = [
        [sg.Text(
            key                 = 'author_error', 
            visible             = False,
            text_color          = 'red',
            background_color    = 'white',
            border_width        = 3,
            justification       = 'center',
            expand_x            = True
        )],
        [col],
        [sg.Button(
            'Delete this author',
            key             = 'delete_author',
            button_color    = 'red',
            pad             = ((10, 0), (30, 0)),
            enable_events   = True,
            border_width    = 2
        )]
    ]
        
    #left column, author selector
    col_1    = sg.Col(
        [
            [
                sg.Text('Search:', size=6),
                sg.I(key='author_search', size=28, enable_events=True, metadata={'table':'Authors'}),
                sg.Text(
                    key                 = 'author_search_count', 
                    visible             = False,
                    text_color          = 'black',
                    border_width        = 3,
                    justification       = 'center',
                    expand_x            = True
                ),
                sg.Listbox(
                    values          = [],
                    select_mode     = sg.LISTBOX_SELECT_MODE_SINGLE,
                    key             = 'search_author_selector',
                    size            = (35,5), 
                    expand_y        = True, 
                    enable_events   = True,
                    no_scrollbar    = False,
                    visible         = False
                )
            ],
            [sg.Button('Add a new author', key='add_author', border_width= 2)],
            [sg.Listbox(
                values          = authors,
                select_mode     = sg.LISTBOX_SELECT_MODE_SINGLE,
                key             = 'author_selector',
                size            = (35,27), 
                expand_y        = True, 
                enable_events   = True,
                no_scrollbar    = no_scrollbar,
                metadata        = {'table': 'Authors', 'clear':'false', 'orderby':'last_name', 'save':False, 'data':data}
            )]
        ],
        justification='left'
    )

    col_2    = sg.Col(
        [[sg.Frame(
            title               = '',
            layout              = details,
            visible             = False,
            key                 = 'author_details_frame',
            border_width        = 0
        )]],
        vertical_alignment  = 'top',
        key = 'author_col_2'
    )

    # Main layout
    layout = [
        [col_1, col_2]
    ]

    return layout