from constants import *

def build_tab():
    if 'program_title' in settings:
        value   = settings['program_title']
    else:
        value   = ''
    row_1    = [
        sg.Text('Program title:', size=30),
        sg.I(
            default_text    = value,
            key             = 'settings_program_title', 
            size            = input_width,
            metadata        = {'table':'Settings', 'column':'value', 'window_refresh':'true'},
            enable_events   = True
        )
    ]

    if 'theme' in settings:
        theme_name  = settings['theme']
    else:
        theme_name          = 'DarkBlue3'
    row_2   = [
        sg.Text('Program theme color:', size=30),
        sg.Combo(
            values          = sg.theme_list(),
            default_value   = theme_name,
            readonly        = True, 
            key             = 'settings_theme',
            size            = input_width-2,
            enable_events   = True,
            metadata        = {'table':'Settings', 'window_refresh':'force'},
        )
    ]

    if 'loan_period' in settings:
        value   = settings['loan_period']
    else:
        value   = ''
    row_3    = [
        sg.Text('Default loan period in days:', size=30),
        sg.I(
            default_text    = value,
            key             = 'settings_loan_period', 
            size            = input_width,
            metadata        = {'table':'Settings', 'column':'value'},
            enable_events   = True
        )
    ]

    if 'item_types' in settings and len(settings['item_types']) > 4:
        no_scrollbar    = False
        width           = input_width-2
    else:
        no_scrollbar   = True
        width           = input_width
    if 'item_types' in settings:
        value   = settings['item_types']
    else:
        value   = ''
    row_4    = [
        sg.Text('Available item types:', size=30),
        sg.Multiline(
            default_text    = value,
            key             = 'settings_item_types', 
            size            = (width,6),
            enable_events   = True,
            no_scrollbar    = no_scrollbar,
            metadata        = {'table':'Settings', 'window_refresh':'true'},
        )
    ]

    if 'max_items' in settings:
        value   = settings['max_items']
    else:
        value   = ''
    row_5    = [
        sg.Text('Default maximum items to loan:', size=30),
        sg.I(
            default_text    = value,
            key             = 'settings_max_items', 
            size            = input_width,
            enable_events   = True,
            metadata        = {'table':'Settings'}
        )
    ]

    if 'prewarning_time' in settings:
        value   = settings['prewarning_time']
    else:
        value   = ''
    row_6    = [
        sg.Text('Pre warning time in days:', size=30),
        sg.I(
            default_text    = value,
            key             = 'settings_prewarning_time', 
            size            = input_width,
            enable_events   = True,
            metadata        = {'table':'Settings'}
        )
    ]

    if 'item_locations' in settings and len(settings['item_locations']) > 4:
        scrollbar   = False
        width       = input_width-2
    else:
        scrollbar   = True
        width       = input_width
    if 'prewarning_time' in settings:
        value   = settings['prewarning_time']
    else:
        value   = ''
    row_7    = [
        sg.Text('Available item locations:', size=30),
        sg.Multiline(
            default_text    = value,
            key             = 'settings_item_locations', 
            size            = (width,6),
            enable_events   = True,
            no_scrollbar    = scrollbar,
            metadata        = {'table':'Settings', 'window_refresh':'true'}
        )
    ]

    # Main layout
    layout = [
        [
            sg.Col(
                [
                    [sg.Text('Settings', justification='center', expand_x = True)],
                    [sg.HorizontalSeparator()],
                    row_1,row_2,row_3,row_4,row_5,row_6,row_7
                ],
                vertical_alignment='top'
            ),
            sg.Col(
                [
                    [sg.Text('Actions', justification='center', expand_x = True)],
                    [sg.HorizontalSeparator()],
                    [sg.Text('Select a file to import Users')],
                    [sg.FileBrowse(
                        button_text     = "Select a file",
                        key             = 'import_users',
                        enable_events   = True,
                        file_types      = (('.CSV Files', '*.csv'),),
                        tooltip         = "Make sure the file has at least one of these headers:\n"+str(userfields).replace('[','').replace(']','')
                    )],
                    [sg.Text('Select a file to import Items')],
                    [sg.FileBrowse(
                        button_text     = "Select a file",
                        key             = 'import_items',
                        enable_events   = True,
                        file_types      = (('.CSV Files', '*.csv'),),
                        tooltip         = "Make sure the file has at least one of these headers:\n"+str(itemfields).replace('[','').replace(']','')
                    )],
                    [sg.Frame(
                        title           = '',
                        border_width    = 0,
                        key             = 'import_progress_frame',
                        visible         = False,
                        layout          = [                
                                [sg.Text(
                                    key             = 'import_progress_message', 
                                    justification   = 'center', 
                                    expand_x        = True,
                                    text_color      = sg.theme_background_color(),
                                    background_color= 'white'
                                )],
                                [
                                    sg.ProgressBar(
                                        orientation 	= 'horizontal',
                                        max_value       = 100,
                                        key             = 'import_progress_bar',
                                        size            = (44,10)
                                    ),
                                    sg.Text(
                                        key             = 'import_progress_percent',
                                    )
                                ],
                            ],
                        )]
                ],
                vertical_alignment  = 'top',
                expand_x            = True,
            )
        ]
    ]

    return layout