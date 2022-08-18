from re import T
from constants import sg, label_width, input_width
from users_tab import loaned_items_frame

def build_tab():
    col_1_1 = sg.Col(
        [
            [sg.Image(source='', size=(10, 15), key='checkout_user_picture')]
        ], 
        vertical_alignment='top'
    )

    #right column within column 1, user details
    col_1_2 = sg.Col(
        [
            [
                sg.Text('Name:', size=label_width),
                sg.Text(key     = 'checkout_display_name', size=input_width)
            ],
            [
                sg.Text('Barcode:', size=label_width),
                sg.Text(key     = 'checkout_user_barcode', size=input_width)
            ]
        ],
        vertical_alignment  = 'top'
    )

    col_2_1 = sg.Col(
        [
            [sg.Image(source='', size=(10, 15), key='checkout_item_picture')]
        ], 
        vertical_alignment='top'
    )

    #right column within column 1, user details
    col_2_2 = sg.Col(
        [
            [
                sg.Text('Title:', size=label_width),
                sg.Text(key = 'checkout_title', size=input_width)
            ],
            [   
                sg.Text('Author:', size=label_width),
                sg.Text(key     = 'checkout_author', size=input_width)
            ],
            [
                sg.Text('Barcode:', size=label_width),
                sg.Text(key     = 'checkout_item_barcode', size=input_width)
            ]
        ],
        vertical_alignment  = 'top'
    )

    col_1   = sg.Col(
        [
            [sg.Text("Enter barcode or name")],
            [sg.I(
                enable_events   = True,
                key             = 'checkout_user_search', 
                focus           = True
            )],
            [
                sg.Text(
                    '', 
                    key                 = 'checkout_user_error', 
                    visible             = False,
                    text_color          = 'red',
                    background_color    = 'white',
                    border_width        = 3,
                    justification       = 'center',
                    expand_x            = True
                )
            ],
            [sg.Frame(
                title='',
                layout=[
                    [col_1_1, col_1_2], 
                    loaned_items_frame('borroweditems')
                ],
                border_width        = 0,
                key                 = 'checkout_user_frame',
                visible             = False
            )]
            
        ],
        vertical_alignment  = 'top',
        size=(500,500)
    )

    col_2   = sg.Col(
        [
            [sg.Text("Enter barcode or title")],
            [sg.I(
                enable_events   = True,
                key             = 'checkout_item_search'
            )],
            [
                sg.Text( 
                    key                 = 'checkout_item_error', 
                    visible             = False,
                    text_color          = 'red',
                    background_color    = 'white',
                    justification       = 'center',
                    expand_x            = True
                )
            ],
            [sg.Frame(
                title='',
                layout=[
                    [col_2_1, col_2_2],
                    [sg.Button(
                        'Check out to',
                        enable_events       = True,
                        pad                 = (0,20),
                        key                 = 'check_out',
                        visible             = False,
                        border_width        = 2
                    )],
                    # We use a frame because unhiding the buttons places them below each other instead of next to each other
                    [sg.Frame(
                        title               = '',
                        layout              = [
                            [
                                sg.Button(
                                    button_text         = 'Return to library',
                                    enable_events       = True,
                                    key                 = 'check_in',
                                    border_width        = 2
                                ),
                                sg.Text(key='due_date',visible=False),
                                sg.Button(
                                    button_text         = 'Extend loan',
                                    enable_events       = True,
                                    key                 = 'extend_loan',
                                    border_width        = 2
                                )
                            ]
                        ],
                        border_width        = 0,
                        visible             = False,
                        key                 = 'check_in_frame'
                    )],
                ],
                border_width        = 0,
                key                 = 'checkout_item_frame',
                visible             = False
            )],
        ],
        vertical_alignment  = 'top',
        size=(500,500)
    )

    row_1   = [
    sg.Text(
        'Check out Desk', 
        font             = 'bold',
        justification    = 'center',
        expand_x         = True
        )
    ]

    row_2 = [
        col_1, sg.VerticalSeparator(),col_2 
    ]


    #row_2   = loaned_items_frame('borroweditems', False)

    # Main layout
    layout = [
        row_1,
        row_2
    ]

    return layout
