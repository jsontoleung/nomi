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

package org.apache.cxf.management.web.logging.logbrowser.client.service.settings;

import javax.annotation.Nonnull;

public class Credentials {
    public static final Credentials EMPTY = new Credentials();
    
    private final String username;
    private final String password;

    private Credentials() {
        this.username = "";
        this.password = "";
    }

    public Credentials(@Nonnull final String username, @Nonnull final String password) {
        assert !"".equals(username);
        assert !"".equals(password);
        
        this.username = username;
        this.password = password;
    }

    @Nonnull
    public String getUsername() {
        return username;
    }

    @Nonnull
    public String getPassword() {
        return password;
    }
}
