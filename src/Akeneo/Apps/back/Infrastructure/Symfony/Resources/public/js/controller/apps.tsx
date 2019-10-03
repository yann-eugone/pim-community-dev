import * as $ from 'jquery';
import * as React from 'react';
import * as ReactDOM from 'react-dom';

import {AppsList} from '@akeneo-pim-ce/apps';

const BaseController = require('pim/controller/base');

class AppsController extends BaseController {
  renderRoute() {
    ReactDOM.render(<AppsList />, this.el);

    return $.Deferred().resolve();
  }
}

export = AppsController;
