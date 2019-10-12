/**
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements. See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership. The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

package org.apache.cxf.management.web.logging.logbrowser.client;

import javax.annotation.Nonnull;
import javax.annotation.Nullable;

import com.google.gwt.user.client.ui.RootLayoutPanel;
import com.google.inject.Inject;
import com.google.inject.Provider;
import org.apache.cxf.management.web.logging.logbrowser.client.event.GoToAccessControlerEvent;
import org.apache.cxf.management.web.logging.logbrowser.client.event.GoToAccessControlerEventHandler;
import org.apache.cxf.management.web.logging.logbrowser.client.event.GoToBrowserEvent;
import org.apache.cxf.management.web.logging.logbrowser.client.event.GoToBrowserEventHandler;
import org.apache.cxf.management.web.logging.logbrowser.client.event.GoToEditCriteriaEvent;
import org.apache.cxf.management.web.logging.logbrowser.client.event.GoToEditCriteriaEventHandler;
import org.apache.cxf.management.web.logging.logbrowser.client.event.GoToSettingsEvent;
import org.apache.cxf.management.web.logging.logbrowser.client.event.GoToSettingsEventHandler;
import org.apache.cxf.management.web.logging.logbrowser.client.event.SignOutEvent;
import org.apache.cxf.management.web.logging.logbrowser.client.event.SignOutEventHandler;
import org.apache.cxf.management.web.logging.logbrowser.client.service.settings.Credentials;
import org.apache.cxf.management.web.logging.logbrowser.client.service.settings.SettingsFacade;
import org.apache.cxf.management.web.logging.logbrowser.client.ui.Presenter;
import org.apache.cxf.management.web.logging.logbrowser.client.ui.accesscontroler.AccessControlPresenter;
import org.apache.cxf.management.web.logging.logbrowser.client.ui.browser.BrowsePresenter;
import org.apache.cxf.management.web.logging.logbrowser.client.ui.browser.EditCriteriaPresenter;
import org.apache.cxf.management.web.logging.logbrowser.client.ui.settings.SettingsPresenter;

import static org.apache.cxf.management.web.logging.logbrowser.client.service.settings.SettingsFacade
        .StorageStrategy.LOCAL_AND_REMOTE;

public class Dispatcher {

    @Nonnull
    private final EventBus eventBus;

    @Nonnull
    private final Provider<AccessControlPresenter> accessControlProvider;

    @Nonnull
    private final Provider<BrowsePresenter> browseProvider;

    @Nonnull
    private final Provider<EditCriteriaPresenter> editCriteriaProvider;

    @Nonnull
    private final Provider<SettingsPresenter> settingsProvider;

    @Nonnull
    private final SettingsFacade settingsFacade;

    @Nullable
    private Presenter currentPresenter;

    @Inject
    public Dispatcher(@Nonnull final EventBus eventBus,
                      @Nonnull final SettingsFacade settingsFacade,
                      @Nonnull final Provider<AccessControlPresenter> accessControlProvider,
                      @Nonnull final Provider<BrowsePresenter> browseProvider,
                      @Nonnull final Provider<EditCriteriaPresenter> editCriteriaProvider,
                      @Nonnull final Provider<SettingsPresenter> settingsProvider) {
        this.eventBus = eventBus;
        this.accessControlProvider = accessControlProvider;
        this.browseProvider = browseProvider;
        this.editCriteriaProvider = editCriteriaProvider;
        this.settingsProvider = settingsProvider;
        this.settingsFacade = settingsFacade;

        bind();
    }

    public void start() {
        if (settingsFacade.isSettingsAlreadyInLocalStorage()) {
            settingsFacade.initialize(LOCAL_AND_REMOTE, Credentials.EMPTY);
            eventBus.fireEvent(new GoToBrowserEvent());
        } else {
            go(accessControlProvider.get());
        }
    }

    private void go(@Nonnull final Presenter newPresenter) {
        if (currentPresenter != null) {
            currentPresenter.unbind();
        }

        currentPresenter = newPresenter;

        currentPresenter.go(RootLayoutPanel.get());
    }

    private void bind() {

        eventBus.addHandler(GoToAccessControlerEvent.TYPE, new GoToAccessControlerEventHandler() {
            public void onGoToAccessControler(@Nonnull final GoToAccessControlerEvent event) {
                go(accessControlProvider.get());
            }
        });

        eventBus.addHandler(GoToBrowserEvent.TYPE, new GoToBrowserEventHandler() {
            public void onGoToBrowser(@Nonnull final GoToBrowserEvent event) {
                go(browseProvider.get());
            }
        });

        eventBus.addHandler(GoToEditCriteriaEvent.TYPE, new GoToEditCriteriaEventHandler() {
            public void onGoToEditCriteria(@Nonnull final GoToEditCriteriaEvent event) {
                go(editCriteriaProvider.get());
            }
        });

        eventBus.addHandler(GoToSettingsEvent.TYPE, new GoToSettingsEventHandler() {

            public void onGoToSettings(@Nonnull final GoToSettingsEvent event) {
                go(settingsProvider.get());
            }
        });

        eventBus.addHandler(SignOutEvent.TYPE, new SignOutEventHandler() {

            public void onSignOut(@Nonnull final SignOutEvent event) {
                settingsFacade.clearMemoryAndLocalStorage();
                go(accessControlProvider.get());
            }
        });
    }
}
